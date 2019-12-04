# PHP Version Audit

[![license](https://img.shields.io/github/license/lightswitch05/php-version-audit.svg)](https://github.com/lightswitch05/php-version-audit/blob/master/LICENSE)
[![last commit](https://img.shields.io/github/last-commit/lightswitch05/php-version-audit.svg)](https://github.com/lightswitch05/php-version-audit/commits/master)
[![commit activity](https://img.shields.io/github/commit-activity/y/lightswitch05/php-version-audit.svg)](https://github.com/lightswitch05/php-version-audit/commits/master)

PHP Version Audit is a convenience tool to easily check a given PHP version against a regularly updated
list of CVE exploits, new releases, and end of life dates.

**PHP Version Audit is not:** exploit detection/mitigation, vendor-specific version tracking, a replacement for
staying informed on PHP releases and security exploits.

## Features:
* List known CVEs for a given version of PHP
* Check either the runtime version of PHP, or a supplied version
* Display end-of-life dates for a given version of PHP
* Display new releases for a given version of PHP with configurable specificity (latest/minor/patch)
    * Patch: 7.2.24 -> 7.2.25
    * Minor: 7.2.24 -> 7.3.12
    * Latest: 5.6.40 -> 7.3.12
* Rules automatically updated twice a day. Information is sourced directly from php.net - you'll never be waiting on someone like me to merge a pull request before getting the latest patch information.
* Multiple interfaces: CLI (via PHP Composer), Docker, direct code import
* Easily scriptable for use with CI/CD workflows. All Docker/CLI outputs are in JSON format to be consumed with your favorite tools - such as [jq](https://stedolan.github.io/jq/).
* Configurable exit conditions. Use CLI flags like `--fail-security` to set a failure exit code if the given version of PHP has a known CVE or is no longer receiving security updates. 

## Usage

### Docker

Check a specific version of PHP using Docker

    docker run --rm lightswitch05/php-version-audit:latest --version=7.3.12

Check the host's PHP version using Docker

    docker run --rm lightswitch05/php-version-audit:latest --version=$(php -r 'echo phpversion();')

### CLI

Install the package via composer

    composer require lightswitch05/php-version-audit:~1.0
    
Execute the PHP script

    ./vendor/bin/php-version-audit

Produce an exit code if any CVEs are found

    ./vendor/bin/php-version-audit --fail-security

### Options

    usage: php-version-audit        [--help] [--version=PHP_VERSION]
                                    [--fail-security] [--fail-support]
                                    [--fail-patch] [--fail-latest]
                                    [--no-update]
    
    optional arguments:
    --help                          show this help message and exit.
    --version                       set the PHP Version to run against. Defaults to the runtime version, be sure to set this if you are using the docker image.
    --fail-security                 generate a 10 exit code if any CVEs are found, or security support has ended.
    --fail-support                  generate a 20 exit code if the version of PHP no longer gets active (bug) support.
    --fail-patch                    generate a 30 exit code if there is a newer patch-level release.
    --fail-latest                   generate a 40 exit code if there is a newer release.
    --no-update                     do not download the latest rules. NOT RECOMMENDED!

## Project Goals:
* Always use update-to-date information and fail if it becomes too stale. Since this tool is designed to help its users stay informed, it must in turn fail if it becomes outdated.
* Fail if the requested information is unavailable. ex. getting the support end date of PHP version 6.0, or 5.7.0. Again, since this tool is designed to help its users stay informed, it must in turn fail if the requested information is unavailable. 
* Work in both open and closed networks (as long as the tool is up-to-date).
* Minimal footprint and dependencies.
* Runtime support for the oldest supported version of PHP. If you are using this tool with an unsupported version of PHP, then you already have all the answers that this tool can give you: Yes, you have vulnerabilities and are out of date. Of course that is just for the run-time, it is still the goal of this project to supply information about any reasonable version of PHP.
