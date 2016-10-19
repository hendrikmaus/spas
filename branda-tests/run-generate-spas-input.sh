#!/usr/bin/env bash

vendor/bin/drafter branda-tests/api-description-altered--spas.apib \
    --output branda-tests/spas-input.apib.refract.json \
    --format json
