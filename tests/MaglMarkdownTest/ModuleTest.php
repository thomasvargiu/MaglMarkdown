<?php

namespace MaglMarkdownTest;

use MaglMarkdown\Module;
use Zend\View\HelperPluginManager;

/**
 * Description of ModuleTest
 *
 * @author matthias
 */
class ModuleTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var Module
     */
    private $instance;

    public function setUp()
    {
        $this->instance = new Module();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('MaglMarkdown\Module', $this->instance);
    }

    public function testConfigSerializable()
    {
        $config = $this->instance->getConfig();

        $this->assertEquals($config, unserialize(serialize($config)));
    }

    public function testGetViewHelperConfig()
    {
        $config = $this->instance->getViewHelperConfig();

        $this->assertTrue(array_key_exists('markdown', $config['factories']));
    }

    public function testGetAutoloaderConfig()
    {
        $config = $this->instance->getAutoloaderConfig();

        $this->assertTrue(array_key_exists('MaglMarkdown', $config['Zend\Loader\StandardAutoloader']['namespaces']));
    }

    public function testGetServiceConfig()
    {
        $config = $this->instance->getConfig();

        $this->assertTrue(array_key_exists('service_manager', $config));
        $this->assertTrue(array_key_exists('MaglMarkdown\Adapter\ErusevParsedownAdapter', $config['service_manager']['invokables']));

        $this->assertTrue(array_key_exists('MaglMarkdown\MarkdownAdapter', $config['service_manager']['aliases']));
    }

    public function testGetServiceFactories()
    {
        $config = $this->instance->getServiceConfig();

        $this->assertTrue(array_key_exists('factories', $config));
        $this->assertTrue(array_key_exists('MaglMarkdown\Adapter\GithubMarkdownAdapter', $config['factories']));
        $this->assertTrue(array_key_exists('MaglMarkdown\Adapter\GithubMarkdownOptions', $config['factories']));
        $this->assertTrue(array_key_exists('MaglMarkdown\Adapter\MichelfPHPMarkdownAdapter', $config['factories']));
        $this->assertTrue(array_key_exists('MaglMarkdown\Adapter\MichelfPHPMarkdownExtraAdapter', $config['factories']));
    }

    public function testGetDefaultAdapter()
    {
        $markdown = Bootstrap::getServiceManager()->get('MaglMarkdown\MarkdownAdapter');

        $this->assertInstanceOf('\MaglMarkdown\Adapter\MarkdownAdapterInterface', $markdown);
        $this->assertInstanceOf('\MaglMarkdown\Adapter\MichelfPHPMarkdownExtraAdapter', $markdown);
    }

    public function testCacheDisabledByDefault()
    {
        $config = $this->instance->getConfig();

        $this->assertFalse($config['magl_markdown']['cache_enabled']);
    }

    public function testGetViewHelper()
    {
        $serviceManager = Bootstrap::getServiceManager();

        /* @var $view HelperPluginManager */
        $view = $serviceManager->get('ViewHelperManager');

        $markdown = $view->get('markdown');
        $this->assertInstanceOf('MaglMarkdown\View\Helper\Markdown', $markdown);
        $this->assertInstanceOf('Zend\View\Helper\HelperInterface', $markdown);
    }

    public function testAddsEventListener()
    {

        $smClone = clone Bootstrap::getServiceManager();
        $smClone->setAllowOverride(true);
        $smClone->setService('Config', array(
            'magl_markdown' => array(
                'cache_enabled' => true
            )
        ));


        $cacheListenerMock = $this->getMockBuilder('\MaglMarkdown\Cache\CacheListener')
            ->disableOriginalConstructor()
            ->getMock();

        $smClone->setService('MaglMarkdown\CacheListener', $cacheListenerMock);

        $emMock = $this->getMockBuilder('\Zend\EventManager\EventManager')
            ->disableOriginalConstructor()
            ->getMock();

        $emMock->expects($this->once())
            ->method('attachAggregate')
            ->with($cacheListenerMock);

        $applicationMock = $this->getMockBuilder('\Zend\Mvc\Application')
            ->disableOriginalConstructor()
            ->getMock();

        $applicationMock->expects($this->once())
            ->method('getServiceManager')
            ->willReturn($smClone);

        $applicationMock->expects($this->once())
            ->method('getEventManager')
            ->willReturn($emMock);

        $eventMock = $this->getMockBuilder('\Zend\Mvc\MvcEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock->expects($this->any())
            ->method('getApplication')
            ->willReturn($applicationMock);


        $this->instance->onBootstrap($eventMock);
    }
}
