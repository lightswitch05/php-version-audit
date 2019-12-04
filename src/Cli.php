<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit;


use lightswitch05\PhpVersionAudit\Exceptions\StaleRulesException;

class Cli
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

    public static function run(): int
    {
        $args = self::getArgs();
        if ($args['help']) {
            self::showHelp();
            return 0;
        }

        $app = new Application($args[self::$PHP_VERSION], $args[self::$NO_UPDATE]);

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
            fwrite(STDOUT, "\n$output\n");

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
            // TODO Add error message here
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
            self::$FULL_UPDATE
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
        ];
    }

    private static function showHelp(): void
    {
        $help = "PHP Version Audit\n";
        $help .= "usage: php-version-audit\t[--help] [--" . self::$PHP_VERSION . "=PHP_VERSION]\n";
        $help .= "\t\t\t\t[--" . self::$FAIL_SECURITY . "] [--" . self::$FAIL_SUPPORT . "]\n";
        $help .= "\t\t\t\t[--" . self::$FAIL_PATCH . "] [--" . self::$FAIL_LATEST . "]\n";
        $help .= "\t\t\t\t[--" . self::$NO_UPDATE . "]\n\n";
        $help .= "optional arguments:\n";
        $help .= "--" . self::$HELP . "\t\t\t\tshow this help message and exit.\n";
        $help .= "--" . self::$PHP_VERSION . "\t\t\tset the PHP Version to run against. Defaults to the runtime version, be sure to set this if you are using the docker image.\n";
        $help .= "--" . self::$FAIL_SECURITY . "\t\t\tgenerate a " . self::$FAIL_SECURITY_CODE . " exit code if any CVEs are found, or security support has ended.\n";
        $help .= "--" . self::$FAIL_SUPPORT. "\t\t\tgenerate a " . self::$FAIL_SUPPORT_CODE . " exit code if the version of PHP no longer gets active (bug) support.\n";
        $help .= "--" . self::$FAIL_PATCH . "\t\t\tgenerate a " . self::$FAIL_PATCH_CODE . " exit code if there is a newer patch-level release.\n";
        $help .= "--" . self::$FAIL_LATEST . "\t\t\tgenerate a " . self::$FAIL_LATEST_CODE . " exit code if there is a newer release.\n";
        $help .= "--" . self::$NO_UPDATE . "\t\t\tdo not download the latest rules. NOT RECOMMENDED!\n";
        fwrite(STDOUT, "$help\n");
    }

    private static function getVersion(array $options): string
    {
        if (isset($options[self::$PHP_VERSION])) {
            return $options[self::$PHP_VERSION];
        }
        return phpversion();
    }

    private static function getOptionalFlag(array $options, string $name): bool
    {
        return isset($options[$name]);
    }
}
