<?php

namespace Drupal\amazons3Test;

use Drupal\amazons3\Matchable\MatchablePaths;
use Drupal\amazons3\StreamWrapperConfiguration;

class StreamWrapperConfigurationTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers Drupal\amazons3\StreamWrapperConfiguration::fromConfig
   * @covers Drupal\amazons3\StreamWrapperConfiguration::defaults
   * @covers Drupal\amazons3\StreamWrapperConfiguration::required
   */
  public function testFromConfig() {
    $settings = array('bucket' => 'bucket');
    $config = StreamWrapperConfiguration::fromConfig($settings);
    $this->assertInstanceOf('Drupal\amazons3\StreamWrapperConfiguration', $config);
  }

  /**
   * @covers Drupal\amazons3\StreamWrapperConfiguration::fromConfig
   * @expectedException \InvalidArgumentException
   */
  public function testFromConfigMissingExpiration() {
    $settings = array('bucket' => 'bucket', 'caching' => TRUE);
    $config = StreamWrapperConfiguration::fromConfig($settings);
  }

  /**
   * @covers Drupal\amazons3\StreamWrapperConfiguration
   */
  public function testSetters() {
    $config = StreamWrapperConfiguration::fromConfig(array('bucket' => 'bucket'));

    $config->setBucket('different-bucket');
    $this->assertEquals('different-bucket', $config->getBucket());

    $config->enableCaching();
    $this->assertTrue($config->isCaching());

    $config->setCacheLifetime(666);
    $this->assertEquals(666, $config->getCacheLifetime());

    $config->disableCaching();
    $this->assertFalse($config->isCaching());

    $config->setDomain('api.example.com');
    $this->assertEquals('api.example.com', $config->getDomain());

    $config->setHostname('cdn.example.com');
    $this->assertEquals('cdn.example.com', $config->getHostname());

    $config->serveWithCloudFront();
    $this->assertTrue($config->isCloudFront());

    $config->serveWithS3();
    $this->assertFalse($config->isCloudFront());

    $mp = new MatchablePaths(array('/'));
    $config->setPresignedPaths($mp);
    $this->assertEquals($mp, $config->getPresignedPaths());

    $config->setReducedRedundancyPaths($mp);
    $this->assertEquals($mp, $config->getReducedRedundancyPaths());

    $config->setSaveAsPaths($mp);
    $this->assertEquals($mp, $config->getSaveAsPaths());

    $config->setTorrentPaths($mp);
    $this->assertEquals($mp, $config->getTorrentPaths());

    $config->serveWithCloudFront();
    $config->setCloudFrontCredentials('/dev/null', 'keypair-id');
    $this->assertInstanceOf('Aws\CloudFront\CloudFrontClient', $config->getCloudFront());
  }

  /**
   * @expectedException \LogicException
   */
  public function testCacheLifetimeException() {
    $config = StreamWrapperConfiguration::fromConfig(array('bucket' => 'bucket'));
    $config->setCacheLifetime(0);
  }

  /**
   * @covers Drupal\amazons3\StreamWrapperConfiguration
   * @expectedException \InvalidArgumentException
   */
  public function testCloudFrontCredentials() {
    $config = StreamWrapperConfiguration::fromConfig(array('bucket' => 'bucket'));
    $config->setCloudFrontCredentials('/does-not-exist', 'asdf');
  }

  /**
   * @covers Drupal\amazons3\StreamWrapperConfiguration
   * @expectedException \LogicException
   */
  public function testCloudFrontNotSetUp() {
    $config = StreamWrapperConfiguration::fromConfig(array('bucket' => 'bucket'));
    $config->getCloudFront();
  }

  /**
   * @covers Drupal\amazons3\StreamWrapperConfiguration::fromConfig
   */
  public function testDefaultHostname() {
    $config = StreamWrapperConfiguration::fromConfig(array('bucket' => 'bucket'));
    $this->assertEquals('s3.amazonaws.com', $config->getDomain());
  }
}
