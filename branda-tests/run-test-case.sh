#!/usr/bin/env bash

bin/spas run \
    --file "branda-tests/spas-input.apib.refract.json" \
    --type apib-refract \
    --base_uri http://localhost:8000 \
    --request_provider "\Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider" \
    --hook hooks/hook-playground.php
