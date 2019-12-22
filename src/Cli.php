<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit;


use lightswitch05\PhpVersionAudit\Exceptions\InvalidArgumentException;
use lightswitch05\PhpVersionAudit\Exceptions\InvalidVersionException;
use lightswitch05\PhpVersionAudit\Exceptions\StaleRulesException;

final class Cli
{
    private static $PHP_VERSION = 'version';
    private static $HELP = 'help';
    private static $NO_UPDATE = 'no-update';
    private static $FULL_UPDATE = 'full-update';
    private static $FAIL_SECURITY = 'fail-security';
    private static $FAIL_SECURITY_CODE = 10;
    private static $FAIL_SUPPORT = 'fail-support';
    private static $FAIL_SUPPORT_CODE = 20;
    private static $FAIL_PATCH = 'fail-patch';
    private static $FAIL_PATCH_CODE = 30;
    private static $FAIL_LATEST = 'fail-latest';
    private static $FAIL_LATEST_CODE = 40;
    private static $FAIL_STALE_CODE = 100;
    private static $INVALID_ARG_CODE = 200;
    private static $INVALID_VERSION_CODE = 300;


    public static function run(): int
    {
        try {
            $args = self::getArgs();
            Logger::setVerbosity($args['verbosity']);
        } catch (InvalidArgumentException $ex) {
            Logger::error($ex->getMessage());
            self::showHelp();
            return self::$INVALID_ARG_CODE;
        }

        if ($args['help']) {
            self::showHelp();
            return 0;
        }

        try {
            $app = new Application($args[self::$PHP_VERSION], $args[self::$NO_UPDATE]);
        } catch (InvalidVersionException $ex) {
            Logger::error($ex->getMessage());
            return self::$INVALID_VERSION_CODE;
        }


        if ($args[self::$FULL_UPDATE]) {
            /**
             * PLEASE DO NOT USE THIS. This function is intended to only be used internally for updating
             * project rules in github, which can then be accessed by ALL instances of PHP Version Audit.
             * Running it locally puts unnecessary load on the source servers and cannot be re-used by others.
             *
             * The github hosted rules are setup on a cron schedule to update multiple times a day.
             * Running it directly will not provide you with any new information and will only
             * waste time and server resources.
             */
            $app->fullRulesUpdate();
        }

        try {
            $auditDetails = $app->getAllAuditDetails();
            $output = json_encode($auditDetails, JSON_PRETTY_PRINT);
            fwrite(STDOUT, "$output" . PHP_EOL);

            if ($args[self::$FAIL_SECURITY] && ($auditDetails->hasVulnerabilities || !$auditDetails->hasSecuritySupport)) {
                return self::$FAIL_SECURITY_CODE;
            }
            if ($args[self::$FAIL_SUPPORT] && (!$auditDetails->hasSecuritySupport || !$auditDetails->hasActiveSupport)) {
                return self::$FAIL_SUPPORT_CODE;
            }
            if ($args[self::$FAIL_LATEST] && !$auditDetails->latestVersion) {
                return self::$FAIL_LATEST_CODE;
            }
            if ($args[self::$FAIL_PATCH] && !$auditDetails->isLatestPatchVersion) {
                return self::$FAIL_PATCH_CODE;
            }
        } catch (StaleRulesException $ex) {
            Logger::error($ex->getMessage());
            return self::$FAIL_STALE_CODE;
        }

        return 0;
    }

    /**
     * @return array
     */
    private static function getArgs(): array
    {
        $options = getopt('', [
            self::$PHP_VERSION . '::',
            self::$HELP,
            self::$FAIL_SECURITY,
            self::$FAIL_LATEST,
            self::$FAIL_PATCH,
            self::$FAIL_SUPPORT,
            self::$NO_UPDATE,
            self::$FULL_UPDATE,
            'silent',
            'v',
            'vv',
            'vvv'
        ]);
        return [
            self::$PHP_VERSION => self::getVersion($options),
            self::$HELP => self::getOptionalFlag($options, self::$HELP),
            self::$FULL_UPDATE => self::getOptionalFlag($options, self::$FULL_UPDATE),
            self::$NO_UPDATE => self::getOptionalFlag($options, self::$NO_UPDATE),
            self::$FAIL_SECURITY => self::getOptionalFlag($options, self::$FAIL_SECURITY),
            self::$FAIL_LATEST => self::getOptionalFlag($options, self::$FAIL_LATEST),
            self::$FAIL_PATCH => self::getOptionalFlag($options, self::$FAIL_PATCH),
            self::$FAIL_SUPPORT => self::getOptionalFlag($options, self::$FAIL_SUPPORT),
            'verbosity' => self::getVerbosity($options)
        ];
    }

    private static function showHelp(): void
    {
        $usageMask = "\t\t\t\t[--%s] [--%s]" . PHP_EOL;
        $argsMask = "--%s\t\t\t%s" . PHP_EOL;
        $argsErrorCodeMask = "--%s\t\t\tgenerate a %s %s" . PHP_EOL;
        printf("%s" . PHP_EOL, "PHP Version Audit");
        printf("%s\t%s" . PHP_EOL, "usage: php-version-audit", "[--help] [--" . self::$PHP_VERSION . "=PHP_VERSION]");
        printf($usageMask, self::$FAIL_SECURITY, self::$FAIL_SUPPORT);
        printf($usageMask, self::$FAIL_PATCH, self::$FAIL_LATEST);
        printf($usageMask, self::$NO_UPDATE, 'silent');
        printf("\t\t\t\t[--%s]" . PHP_EOL, 'v');
        printf("%s" . PHP_EOL, "optional arguments:");
        printf($argsMask, self::$HELP,"\tshow this help message and exit.");
        printf($argsMask, self::$PHP_VERSION,"set the PHP Version to run against. Defaults to the runtime version. This is required when running with docker.");
        printf($argsErrorCodeMask, self::$FAIL_SECURITY, self::$FAIL_SECURITY_CODE, "exit code if any CVEs are found, or security support has ended.");
        printf($argsErrorCodeMask, self::$FAIL_SUPPORT, self::$FAIL_SUPPORT_CODE, "exit code if the version of PHP no longer gets active (bug) support.");
        printf($argsErrorCodeMask, self::$FAIL_PATCH, self::$FAIL_PATCH_CODE, "exit code if there is a newer patch-level release.");
        printf($argsErrorCodeMask, self::$FAIL_LATEST, self::$FAIL_LATEST_CODE, "exit code if there is a newer release.");
        printf($argsMask, self::$NO_UPDATE, "do not download the latest rules. NOT RECOMMENDED!");
        printf($argsMask, 'silent', "do not write any error messages to STDERR.");
        printf($argsMask, 'v', "\tSet verbosity. v=warnings, vv=info, vvv=debug. Default is error. All logging writes to STDERR.");
    }

    private static function getVersion(array $options): string
    {
        if (isset($options[self::$PHP_VERSION]) && !empty($options[self::$PHP_VERSION])) {
            return $options[self::$PHP_VERSION];
        }
        if (getenv('REQUIRE_VERSION_ARG', true) === 'true') {
            throw new InvalidArgumentException("Missing required argument: --version");
        }
        return phpversion();
    }

    private static function getOptionalFlag(array $options, string $name): bool
    {
        return isset($options[$name]);
    }

    private static function getVerbosity(array $options): int
    {
        if (self::getOptionalFlag($options, 'vvv')) {
            return Logger::DEBUG;
        }
        if (self::getOptionalFlag($options, 'vv')) {
            return Logger::INFO;
        }
        if (self::getOptionalFlag($options, 'v')) {
            return Logger::WARNING;
        }
        if (self::getOptionalFlag($options, 'silent')) {
            return Logger::SILENT;
        }
        return Logger::ERROR;
    }
}
