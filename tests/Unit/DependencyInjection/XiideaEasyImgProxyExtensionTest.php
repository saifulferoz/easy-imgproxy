<?php

declare(strict_types=1);

namespace Xiidea\EasyImgProxyBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Xiidea\EasyImgProxyBundle\DependencyInjection\XiideaEasyImgProxyExtension;
use Xiidea\EasyImgProxyBundle\Preset\PresetRegistry;
use Xiidea\EasyImgProxyBundle\Service\ImgProxyUrlGenerator;

class XiideaEasyImgProxyExtensionTest extends TestCase
{
    private XiideaEasyImgProxyExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new XiideaEasyImgProxyExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadSetsRequiredParameters(): void
    {
        $configs = [[
            'key' => 'test_key_123',
            'salt' => 'test_salt_456',
            'base_url' => 'https://imgproxy.example.com',
        ]];

        $this->extension->load($configs, $this->container);

        $this->assertSame('test_key_123', $this->container->getParameter('xiidea_easy_img_proxy.key'));
        $this->assertSame('test_salt_456', $this->container->getParameter('xiidea_easy_img_proxy.salt'));
        $this->assertSame('https://imgproxy.example.com', $this->container->getParameter('xiidea_easy_img_proxy.base_url'));
    }

    public function testLoadSetsOptionalBooleanParameters(): void
    {
        $configs = [[
            'key' => 'key',
            'salt' => 'salt',
            'base_url' => 'https://imgproxy.example.com',
            'presets_only' => true,
            'enable_pro' => true,
        ]];

        $this->extension->load($configs, $this->container);

        $this->assertTrue($this->container->getParameter('xiidea_easy_img_proxy.presets_only'));
        $this->assertTrue($this->container->getParameter('xiidea_easy_img_proxy.enable_pro'));
    }

    public function testLoadSetsDefaultBooleanParameters(): void
    {
        $configs = [[
            'key' => 'key',
            'salt' => 'salt',
            'base_url' => 'https://imgproxy.example.com',
        ]];

        $this->extension->load($configs, $this->container);

        $this->assertFalse($this->container->getParameter('xiidea_easy_img_proxy.presets_only'));
        $this->assertFalse($this->container->getParameter('xiidea_easy_img_proxy.enable_pro'));
    }

    public function testLoadRegistersServicesViasPhpFileLoader(): void
    {
        $configs = [[
            'key' => 'key',
            'salt' => 'salt',
            'base_url' => 'https://imgproxy.example.com',
        ]];

        // The services are loaded via PhpFileLoader during load()
        // This test verifies the load method completes without error
        // and parameters are set (which enables services to be properly configured)
        $this->extension->load($configs, $this->container);

        // Services will be registered through the PhpFileLoader
        $this->assertTrue($this->container->has(PresetRegistry::class));
        $this->assertTrue($this->container->has(ImgProxyUrlGenerator::class));
    }

    public function testLoadRegistersPresetsInRegistry(): void
    {
        $configs = [[
            'key' => 'key',
            'salt' => 'salt',
            'base_url' => 'https://imgproxy.example.com',
            'presets' => [
                'thumbnail' => [
                    'options' => ['width' => 100, 'height' => 100],
                    'extension' => 'jpg',
                ],
                'medium' => [
                    'options' => ['width' => 500, 'height' => 500],
                ],
            ],
        ]];

        $this->extension->load($configs, $this->container);

        $registryDef = $this->container->getDefinition(PresetRegistry::class);
        $calls = $registryDef->getMethodCalls();

        // Verify presets were registered with the registry
        $presetCalls = array_filter($calls, fn($call) => $call[0] === 'register');
        $this->assertCount(2, $presetCalls);
    }

    public function testLoadWithEmptyPresets(): void
    {
        $configs = [[
            'key' => 'key',
            'salt' => 'salt',
            'base_url' => 'https://imgproxy.example.com',
            'presets' => [],
        ]];

        $this->extension->load($configs, $this->container);

        $registryDef = $this->container->getDefinition(PresetRegistry::class);
        $calls = $registryDef->getMethodCalls();
        $presetCalls = array_filter($calls, fn($call) => $call[0] === 'register');

        $this->assertCount(0, $presetCalls);
    }

    public function testLoadWithoutPresetsKey(): void
    {
        $configs = [[
            'key' => 'key',
            'salt' => 'salt',
            'base_url' => 'https://imgproxy.example.com',
        ]];

        $this->extension->load($configs, $this->container);

        // Should not throw an error, presets should be empty
        $this->assertTrue($this->container->has(PresetRegistry::class));
    }

    public function testImgProxyUrlGeneratorDefinitionExists(): void
    {
        $configs = [[
            'key' => 'test_key',
            'salt' => 'test_salt',
            'base_url' => 'https://imgproxy.example.com',
            'presets_only' => true,
            'enable_pro' => false,
        ]];

        $this->extension->load($configs, $this->container);

        // Verify the service definition exists
        $this->assertTrue($this->container->has(ImgProxyUrlGenerator::class));
        $generatorDef = $this->container->getDefinition(ImgProxyUrlGenerator::class);
        $this->assertNotNull($generatorDef);
    }
}