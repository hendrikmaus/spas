# Spas
That sounds like "spa", but plural.  
It tests your API description against a given environment using real HTTP requests.

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg)](https://php.net/)
[![Build Status](https://travis-ci.org/hendrikmaus/spas.svg?branch=master)](https://travis-ci.org/hendrikmaus/spas)
[![Test Coverage](https://codeclimate.com/github/hendrikmaus/spas/badges/coverage.svg)](https://codeclimate.com/github/hendrikmaus/spas/coverage)
[![Code Climate](https://codeclimate.com/github/hendrikmaus/spas/badges/gpa.svg)](https://codeclimate.com/github/hendrikmaus/spas)

## Note
Spas is currently **alpha**, so its API is subject to change!

## How It Works / Example
There is a running example in the `example` dir including a readme to follow along.

In order to use spas, you'll need:
- your api description parser, e.g. drafter for api blueprint
- spas itself, which is api description agnostic
- spas-request-parser implementation, to understand your api description parse result

Running spas looks like:
- get parse result from your api description parser
- call spas with it

## Installation

### Docker
tbd.

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

## Hooks
The hook system is what makes spas pretty flexible. It allows you to add bits and pieces of
code to manipulate requests, assert outcomes and much more.

There are a few pre-built hooks for you to examine and try out.  
Simply browse `/src/Hook` to see pre-built hooks; all the ones prefixed with `Hello` are examples to learn from.

### Run Hook
Take your spas command add an option:

```bash
--hook "\Hmaus\Spas\Hook\HelloWorld"
```

As you can see, the hooks are simply passed using their fully qulified class name. So as long as the classes
sit inside thee autloader, you can use them right away.

To pass multiple hooks, simply repeat the `--hook` option for every one of them.

```bash
--hook "\Hmaus\Spas\Hook\HelloWorld"
--hook "\Hmaus\Spas\Hook\HelloHookData"
```

### Pass Data to Hooks
Many of your hooks shall be flexible in what they can do, hence you want to configure them from the outside.  
We suggest to use JSON format to pass data into a hook like so:

```bash
--hook "\Hmaus\Spas\Hook\HelloHookData"
--hook_data $'{
    "HelloHookData": {
        "apikey": "ewifvweilfvf"
    },
    "SomeOtherHook": {
        "hook-data-option": "contains all data passed to all hooks"
    }
}'
```

### Write Your Own Hooks
Once you examine the existing hooks, you should already gathered all there is to know.  
Create a new class that is ato-loadable, extend `\Hmaus\Spas\Hook\Hook`, implement the one abstract
method and check Hook's API and the pre-built examples for best practices.
