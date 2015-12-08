<?php namespace Lanin\Laravel\SetupWizard\Tests\Commands\Steps;

use Lanin\Laravel\SetupWizard\Commands\Setup;
use Lanin\Laravel\SetupWizard\Commands\Steps\DotEnv;
use Lanin\Laravel\SetupWizard\Tests\TestCase;

class DotEnvTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown(); // TODO: Change the autogenerated stub

        if (file_exists($this->getFixturePath('.env_save')))
        {
            unlink($this->getFixturePath('.env_save'));
        }
    }

    /**
     * @return \Mockery\Mock
     */
    public function mockCommand()
    {
        $command = \Mockery::mock(Setup::class)->makePartial();
        $command->shouldReceive('confirm')->with('Everything is right?')->andReturn(true);
        $command->shouldReceive('getLaravel')->andReturn($this->app);

        return $command;
    }

    /** @test */
    public function it_has_prompt_text()
    {
        $step = new DotEnv($this->mockCommand());
        $this->assertEquals('Do you want to create .env file?', $step->prompt());
    }

    /** @test */
    public function it_has_prompt_update_text()
    {
        $step = \Mockery::mock(DotEnv::class, [$this->mockCommand()])->makePartial()->shouldAllowMockingProtectedMethods();
        $step->shouldReceive('envFileExist')->andReturn(true)->once();
        $this->assertEquals('Do you want to update .env file?', $step->prompt());
    }

    /** @test */
    public function it_checks_if_env_file_exists()
    {
        $step = new DotEnv($this->mockCommand());
        $envFileExist = $this->getPublicMethod('envFileExist', $step);

        $this->assertFalse($envFileExist->invoke($step));
    }

    /** @test */
    public function it_can_generate_prompts()
    {
        $step = new DotEnv($this->mockCommand());
        $generatePrompt = $this->getPublicMethod('generatePrompt', $step);
        $this->assertEquals(
            'Set database host. <comment>DB_HOST=?</comment>',
            $generatePrompt->invokeArgs($step, ['DB_HOST', 'Set database host.'])
        );
    }

    /** @test */
    public function it_can_ask_for_input()
    {
        $name = 'DB_HOST';
        $options = [
            'prompt' => 'Set database host.',
            'type' => DotEnv::INPUT,
        ];
        $default = 'localhost';

        $command = $this->mockCommand();
        $command->shouldReceive('ask')->with($options['prompt'] . ' <comment>' . $name . '=?</comment>', $default)->andReturn($default)->once();

        $step = new DotEnv($command);
        $runInput = $this->getPublicMethod('runInput', $step);
        $this->assertEquals($default, $runInput->invokeArgs($step, [$name, $options, $default]));
    }

    /** @test */
    public function it_can_ask_for_choice()
    {
        $name = 'APP_DEBUG';
        $options = [
            'prompt' => 'Enable debug mode?',
            'type' => DotEnv::SELECT,
            'options' => ['true', 'false'],
        ];
        $default = 'true';

        $command = $this->mockCommand();
        $command->shouldReceive('choice')->with(
            $options['prompt'] . ' <comment>' . $name . '=?</comment>',
            $options['options'],
            array_search($default, $options['options'])
        )->andReturn($default)->once();

        $step = new DotEnv($command);
        $runSelect = $this->getPublicMethod('runSelect', $step);
        $this->assertEquals($default, $runSelect->invokeArgs($step, [$name, $options, $default]));
    }

    /** @test */
    public function it_can_ask_for_random_value()
    {
        $name = 'APP_KEY';
        $options = [
            'prompt' => 'Application unique key. For initial setup better to leave random.',
            'type' => DotEnv::RANDOM,
        ];
        $default = 'SomeRandomString';

        $command = $this->mockCommand();
        $command->shouldReceive('ask')->with(
            $options['prompt'] . ' <comment>' . $name . '=?</comment>',
            $default
        )->andReturn($default)->once();

        $step = new DotEnv($command);
        $runRandom = $this->getPublicMethod('runRandom', $step);
        $this->assertNotEquals($default, $runRandom->invokeArgs($step, [$name, $options, $default]));
    }

    /** @test */
    public function it_can_ask_for_random_value_but_use_provided()
    {
        $name = 'APP_KEY';
        $options = [
            'prompt' => 'Application unique key. For initial setup better to leave random.',
            'type' => DotEnv::RANDOM,
        ];
        $default = 'SomeRandomString';

        $command = $this->mockCommand();
        $command->shouldReceive('ask')->with(
            $options['prompt'] . ' <comment>' . $name . '=?</comment>',
            $default
        )->andReturn('qwe123')->once();

        $step = new DotEnv($command);
        $runRandom = $this->getPublicMethod('runRandom', $step);
        $this->assertEquals('qwe123', $runRandom->invokeArgs($step, [$name, $options, $default]));
    }

    /** @test */
    public function it_can_generate_random_strings()
    {
        $step = new DotEnv($this->mockCommand());
        $getRandomKey = $this->getPublicMethod('getRandomKey', $step);

        $this->assertTrue(strlen($getRandomKey->invoke($step, 'AES-128-CBC')) == 16);
        $this->assertTrue(strlen($getRandomKey->invoke($step, '')) == 32);
    }

    /** @test */
    public function it_can_show_preview()
    {
        $command = $this->mockCommand();
        $command->shouldReceive('table')->with(
            ['Variable', 'Value'],
            [
                ['APP_DEBUG', 'true'],
                ['APP_KEY', 'qwe123'],
                ['DB_HOST', 'localhost'],
            ]
        )->once();

        $step = new DotEnv($command);
        $this->assertNull($step->preview([
            'APP_DEBUG' => 'true', 'APP_KEY' => 'qwe123', 'DB_HOST' => 'localhost',
        ]));
    }

    /** @test */
    public function it_can_prepare_values()
    {
        $this->app->useEnvironmentPath($this->getFixturePath());

        $command = $this->mockCommand();
        $command->shouldReceive('ask')->andReturn('value')->times(10);
        $command->shouldReceive('choice')->andReturn('value')->times(6);

        $step = \Mockery::mock(DotEnv::class, [$command])->makePartial()->shouldAllowMockingProtectedMethods();
        $step->shouldReceive('envFileToUseForDefaults')->andReturn('.env');
        $step->shouldReceive('envFilePath')->andReturn($this->getFixturePath());

        $this->assertCount(16, $step->prepare());
    }

    /** @test */
    public function it_can_set_env_file_path()
    {
        $step = new DotEnv($this->mockCommand());
        $envFilePath = $this->getPublicMethod('envFilePath', $step);

        $this->assertEquals($this->getBasePath() . '/.env', $envFilePath->invoke($step, '.env'));
    }

    /** @test */
    public function it_can_finish_successfully()
    {
        $command = $this->mockCommand();
        $command->shouldReceive('info')->with('New .env file was saved.')->once();

        $step = \Mockery::mock(DotEnv::class, [$command])->makePartial()->shouldAllowMockingProtectedMethods();
        $step->shouldReceive('saveFile')->with([])->andReturn(true);

        $this->assertTrue($step->finish([]));
    }

    /** @test */
    public function it_can_ask_what_file_to_use_for_defaults()
    {
        $command = $this->mockCommand();
        $command->shouldReceive('confirm')->with('Existing .env file was found. Use it for defaults?', true)->once()->andReturn(true);

        $step = \Mockery::mock(DotEnv::class, [$command])->makePartial()->shouldAllowMockingProtectedMethods();
        $step->shouldReceive('envFileExist')->andReturn(true);

        $envFileToUseForDefaults = $this->getPublicMethod('envFileToUseForDefaults', $step);
        $this->assertEquals('.env', $envFileToUseForDefaults->invoke($step));
    }

    /** @test */
    public function it_can_finish_error()
    {
        $command = $this->mockCommand();
        $command->shouldReceive('error')->with('Failed to save .env file. Check permissions please.')->once();

        $step = \Mockery::mock(DotEnv::class, [$command])->makePartial()->shouldAllowMockingProtectedMethods();
        $step->shouldReceive('saveFile')->with([])->andReturn(false);

        $this->assertFalse($step->finish([]));
    }

    /** @test */
    public function it_can_save_env_file()
    {
        $command = $this->mockCommand();

        $step = \Mockery::mock(DotEnv::class, [$command])->makePartial()->shouldAllowMockingProtectedMethods();
        $step->shouldReceive('envFilePath')->with('.env')->andReturn($this->getFixturePath('.env_save'));

        $saveFile = $this->getPublicMethod('saveFile', $step);
        $return = $saveFile->invoke($step, ['APP_DEBUG' => 'true']);

        $this->assertFileExists($this->getFixturePath('.env_save'));
        $this->assertStringEqualsFile($this->getFixturePath('.env_save'), "APP_DEBUG=true\n");
        $this->assertTrue($return);
    }

}