<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Nette\DI\Compiler;
use Nettrine\ORM\EntityManagerDecorator;
use Nettrine\ORM\Exception\Logical\InvalidArgumentException;
use stdClass;
use Tests\Fixtures\Dummy\DummyConfiguration;
use Tests\Fixtures\Dummy\DummyEntityManagerDecorator;
use Tests\Fixtures\Dummy\DummyFilter;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class OrmExtensionTest extends TestCase
{

	public function testOk(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->build();

		$this->assertInstanceOf(EntityManagerDecorator::class, $container->getService('nettrine.orm.entityManagerDecorator'));
	}

	public function testCustomEntityManager(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig([
					'nettrine.orm' => [
						'entityManagerDecoratorClass' => DummyEntityManagerDecorator::class,
						'configurationClass' => DummyConfiguration::class,
					],
				]);
			})
			->build();

		$this->assertInstanceOf(DummyEntityManagerDecorator::class, $container->getByType(DummyEntityManagerDecorator::class));
		$this->assertInstanceOf(DummyConfiguration::class, $container->getByType(DummyConfiguration::class));
	}

	public function testFilters(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addConfig([
					'nettrine.orm' => [
						'configuration' => [
							'filters' => [
								'autoEnabledFilter' => [
									'class' => DummyFilter::class,
									'enabled' => true,
								],
								'autoDisabledFilter' => [
									'class' => DummyFilter::class,
								],
							],
						],
					],
				]);
			})
			->build();
		/** @var EntityManagerDecorator $em */
		$em = $container->getService('nettrine.orm.entityManagerDecorator');
		$filters = $em->getFilters();

		$this->assertEquals(true, $filters->has('autoEnabledFilter'));
		$this->assertEquals(true, $filters->isEnabled('autoEnabledFilter'));

		$this->assertEquals(true, $filters->has('autoDisabledFilter'));
		$this->assertEquals(false, $filters->isEnabled('autoDisabledFilter'));
	}

	public function testConfigurationException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Configuration class must be subclass of Doctrine\ORM\Configuration, stdClass given.');

		ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig([
					'nettrine.orm' => [
						'configurationClass' => stdClass::class,
					],
				]);
			})
			->build();
	}

}
