<?php

/**
 * @file
 * Contains \Drupal\amazons3\Form\SettingsForm.
 */

namespace Drupal\amazons3\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  /**
     * The date formatter service.
     *
     * @var \Drupal\Core\Datetime\DateFormatter
     */
    protected $dateFormatter;

    /**
     * Constructs a CronForm object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The factory for configuration objects.
     * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
     *   The date formatter service.
     */
    public function __construct(ConfigFactoryInterface $config_factory, DateFormatter $date_formatter) {
      parent::__construct($config_factory);
      $this->dateFormatter = $date_formatter;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
      return new static(
        $container->get('config.factory'),
        $container->get('date.formatter')
      );
    }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazons3_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amazons3.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amazons3.settings');

    $form['amazons3_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Amazon S3 API Key'),
      '#default_value' => $config->get('key'),
      '#required' => TRUE,
    );

    $form['amazons3_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Amazon S3 API Secret'),
      '#default_value' => $config->get('secret'),
      '#required' => TRUE,
    );

    $form['amazons3_bucket'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default Bucket Name'),
      '#default_value' => $config->get('bucket'),
      '#required' => TRUE,
      '#element_validate' => array('amazons3_form_bucket_validate'),
    );

    $form['amazons3_cache'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable metadata caching'),
      '#description' => $this->t('Enable a local file metadata cache to reduce calls to S3.'),
      '#default_value' => $config->get('cache'),
    );

    $options = array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400);
    $form['amazons3_cache_expiration'] = array(
      '#type' => 'select',
      '#title' => $this->t('Expiration of cached file metadata'),
      '#default_value' => $config->get('cache_expiration'),
      '#options' => array(0 => $this->t('Never')) + array_map(array($this->dateFormatter, 'formatInterval'), array_combine($options, $options)),
      '#description' => $this->t('The maximum time Amazon S3 file metadata will be cached. If multiple API clients are interacting with the same S3 buckets, this setting might need to be reduced or disabled.'),
      '#states' => array(
        'visible' => array(
          ':input[name="amazons3_cache"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['amazons3_cname'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CNAME'),
      '#description' => $this->t('Serve files from a custom domain by using an appropriately named bucket e.g. "mybucket.mydomain.com"'),
      '#default_value' => $config->get('cname'),
    );

    $form['amazons3_domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('CDN Domain Name'),
      '#description' => $this->t('If serving files from CloudFront then the bucket name can differ from the domain name.'),
      '#default_value' => $config->get('domain'),
      '#states' => array(
        'visible' => array(
          ':input[id=edit-amazons3-cname]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['amazons3_cloudfront'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CloudFront'),
      '#description' => $this->t('Deliver URLs through a CloudFront domain when using presigned URLs.'),
      '#default_value' => $config->get('cloudfront'),
      '#states' => array(
        'visible' => array(
          ':input[id=edit-amazons3-cname]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['amazons3_hostname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom Hostname'),
      '#description' => $this->t('For use with an alternative API compatible service e.g. <a href="@cloud">Google Cloud Storage</a>', array('@cloud' => 'https://cloud.google.com/storage‎')),
      '#default_value' => $config->get('hostname'),
    );

    $form['amazons3_torrents'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Torrents'),
      '#description' => $this->t('A list of paths that should be delivered through a torrent url. Enter one value per line e.g. "mydir/*". Paths are relative to the Drupal file directory and use patterns as per <a href="@preg_match">preg_match</a>. This won\'t work for CloudFront presigned URLs.', array('@preg_match' => 'http://php.net/preg_match')),
      '#default_value' => implode("\n", $config->get('torrents')),
      '#rows' => 10,
    );

    $lines = array();
    foreach ($config->get('presigned_urls') as $option) {
      $lines[] = $option['timeout'] . '|' . $option['pattern'];
    }

    $form['amazons3_presigned_urls'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Presigned URLs'),
      '#description' => $this->t('A list of timeouts and paths that should be delivered through a presigned url. Enter one value per line, in the format &lt;timeout&gt;|&lt;path&gt;|&lt;protocol&gt;. e.g. "60|mydir/*" or "60|mydir/*|https". Paths are relative to the Drupal file directory and use patterns as per <a href="@preg_match">preg_match</a>.', array('@preg_match' => 'http://php.net/preg_match')),
      '#default_value' => implode("\n", $lines),
      '#rows' => 10,
    );

    $form['amazons3_saveas'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Force Save As'),
      '#description' => $this->t('A list of paths that force the user to save the file by using Content-disposition header. Prevents autoplay of media. Enter one value per line. e.g. "mydir/*". Paths are relative to the Drupal file directory and use patterns as per <a href="@preg_match">preg_match</a>. Files must use a presigned url to use this, however it won\'t work for CloudFront presigned URLs and you\'ll need to set the content-disposition header in the file metadata before saving.', array('@preg_match' => 'http://php.net/preg_match')),
      '#default_value' => implode("\n", $config->get('saveas')),
      '#rows' => 10,
    );

    $form['amazons3_rrs'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Reduced Redundancy Storage'),
      '#description' => $this->t('A list of paths that save the file in <a href="@rrs">Reduced Redundancy Storage</a>. Enter one value per line. e.g. "styles/*". Paths are relative to the Drupal file directory and use patterns as per <a href="@preg_match">preg_match</a>.', array('@rrs' => 'http://aws.amazon.com/s3/faqs/#rrs_anchor', '@preg_match' => 'http://php.net/preg_match')),
      '#default_value' => implode("\n", $config->get('rrs')),
      '#rows' => 10,
    );

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    /**
     * @TODO Update cloudfront integration
    $cloudfront = $form_state['values']['amazons3_cloudfront'];

    if ($cloudfront) {
      $keypair = variable_get('aws_cloudfront_keypair', '');
      $pem = variable_get('aws_cloudfront_pem', '');
      if (empty($keypair) || empty($pem)) {
        form_set_error('amazons3_cloudfront', t('You must configure your CloudFront credentials in the awksdk module.'));
      }
    }
    */

    parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keys = array(
      'amazons3_rrs',
      'amazons3_saveas',
      'amazons3_torrents',
    );

    $values = [];
    foreach ($keys as $form_key) {
      $values[$form_key] = explode("\n", $form_state->getValue($form_key));
      $values[$form_key] = array_map('trim', $values[$form_key]);
      $values[$form_key] = array_filter($values[$form_key], 'strlen');
    }

    // Presigned URLs are special in that they are pipe-separated lines.
    $presigned_config = array();
    foreach ($form_state->getValue('amazons3_presigned_urls') as $presigned_line) {
      list($timeout, $pattern) = explode("|", $presigned_line);
      $presigned_config[] = array(
        'timeout' => $timeout,
        'pattern' => $pattern,
      );
    }
    $values['amazons3_presigned_urls'] = $presigned_config;



    $this->config('amazons3.settings')
      ->set('key', $form_state->getValue('amazons3_key'))
      ->set('secret', $form_state->getValue('amazons3_secret'))
      ->set('bucket', $form_state->getValue('amazons3_bucket'))
      ->set('cache', $form_state->getValue('amazons3_cache'))
      ->set('cache_expiration', $form_state->getValue('amazons3_cache_expiration'))
      ->set('cname', $form_state->getValue('amazons3_cname'))
      ->set('domain', $form_state->getValue('amazons3_domain'))
      ->set('cloudfront', $form_state->getValue('amazons3_cloudfront'))
      ->set('hostname', $form_state->getValue('amazons3_hostname'))
      ->set('torrents', $values['amazons3_torrents'])
      ->set('rrs', $values['amazons3_rrs'])
      ->set('presigned_urls', $values['amazons3_presigned_urls'])
      ->set('saveas', $values['amazons3_saveas'])
      ->save();


    parent::submitForm($form, $form_state);
  }


}
