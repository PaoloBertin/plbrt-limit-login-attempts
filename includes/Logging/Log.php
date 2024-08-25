<?php

namespace Pressidium\Limit_Login_Attempts\Logging;

use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Level;
use Psr\Log\LoggerInterface;

final class Log
{
    /** @var string  */
    // private const OUTPUT_STREAM = __DIR__ . '/logs/plugin.log';
    private const OUTPUT_STREAM = WP_PLUGIN_DIR . '/logs/plugin.log';

    /**
     * @var LoggerInterface
     */
    protected static $instance;

    public static function getLogger(): LoggerInterface
    {
        if (!self::$instance) {
            $plugin_dir = WP_PLUGIN_DIR . '/prsdm-limit-login-attempts' . '/';
            // $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv = Dotenv::createImmutable($plugin_dir);
            $dotenv->load();
            $dotenv->required(['LOGGING_LEVEL', ])->notEmpty();
            self::configureInstance();
        }
        return self::$instance;
    }

    protected static function configureInstance(): LoggerInterface
    {
        $logger = new Logger('log');
        $logger->pushHandler(new StreamHandler(self::OUTPUT_STREAM, self::getLoggingLevel()));
        // $logger->pushHandler(new StreamHandler(__DIR__ . '/logs/plugin.log', self::getLoggingLevel()));
        // $this->logger->pushHandler(new StreamHandler(__DIR__ . '/logs/plugin.log', Logger::DEBUG));


        self::$instance = $logger;
        return self::$instance;
    }

    public static function getLoggingLevel()
    {
        // $loggin_level = getenv('LOGGING_LEVEL');
        $loggin_level = $_ENV['LOGGING_LEVEL'];
        if ($loggin_level === 'debug') {
            //            return Logger::DEBUG;
            return Level::Debug;
        }
        return Level::Error;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::getLogger()->notice($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::getLogger()->alert($message, $context);
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::getLogger()->emergency($message, $context);
    }
}
