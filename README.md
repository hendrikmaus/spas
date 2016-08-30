# Spas
API Description HTTP tester.

## Flow
- to use spas, you need something that implements spas-parser
- in your app do:
    - read api description file, e.g. api blueprint
    - pass it into the parser, e.g. drafter using drafter-php
    - pass the parse result into a concrete spas-parser implementation
        - e.g. spas-parser-apib-refract (can use hmaus/reynaldo)
    - pass the ParsedRequest's into spas
        - exit 1 or 0 depending on spas' result
    - pass the parse result into a static site generator -> HTML
        - amando is building this using hmaus/reynaldo and sculpin
    - done.
