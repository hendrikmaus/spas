# Spas
That sounds like "spa", but plural.  
It tests your API description against a given environment using real HTTP request.

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg)](https://php.net/)
[![Build Status](https://travis-ci.org/hendrikmaus/spas.svg?branch=master)](https://travis-ci.org/hendrikmaus/spas)
[![Test Coverage](https://codeclimate.com/github/hendrikmaus/spas/badges/coverage.svg)](https://codeclimate.com/github/hendrikmaus/spas/coverage)
[![Code Climate](https://codeclimate.com/github/hendrikmaus/spas/badges/gpa.svg)](https://codeclimate.com/github/hendrikmaus/spas)

## Note
Spas is currently **experimental**!

## How It Works / Example
There is a running example in the `example` dir including a readme to follow along.

In order to use spas, you'll need:
- your api description parser, e.g. drafter for api blueprint
- spas itself, which is api description agnostic
- spas-request-parser implementation, to understand your api description parse result

Running spas looks like:
- get parse result from your api description parser
- call spas with it

## Installation And Usage

### Docker
tbd

### From Source
The recommended way to install spas & co is by using [composer](https://getcomposer.org).

#### API Description Parser
In this example, we'll install [drafter](https://github.com/apiaryio/drafter) to work with API Blueprint.

```bash
composer require hmaus/drafter-installer
```

Now [configure the drafter installer](https://github.com/hendrikmaus/drafter-installer) accordingly.
Make sure to actually install drafter, usually you'll add a script to call
`composer install-drafter`during the config, and check it using `vendor/bin/drafter -v`.  

#### Request Parser
> Please see [a guide to create a request parser](https://github.com/hendrikmaus/spas-parser) if your parser
> is not yet supported

```bash
composer require hmaus/spas-parser-apib
```

This will get you `\Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider` to put into spas' `request_provider` option

#### Spas
Now we can install spas itself:

```bash
composer require hmaus/spas
```

With a default composer config, spas should now be available as `vendor/bin/spas`.
