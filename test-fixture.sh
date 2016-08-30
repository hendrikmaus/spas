#!/usr/bin/env bash
bin/spas run \
    --input "tests/fixtures/09. Advanced Attributes.md.refract.json" \
    --input_type apib-refract \
    --base_uri http://localhost:3000 \
    --request_provider "\Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider" \
    --hook hooks/hook-playground.php \
    -vvv