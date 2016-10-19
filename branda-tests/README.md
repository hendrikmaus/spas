# Branda Test Cases
This is a highly experimental draft for a test mechanism in spas.

The basic idea is, to create scenrios that force spas to output errors.
That way, we can examine how the validators behave on a real shell.

## Important
- Run test cases from the *root* of the project
- Design test cases as if they are ran from the project root

## Running The Tests
Open a shell and navigate to the root of your spas checkout.

```bash
cd /path/to/spas
```

If you want to re-generate the input for spas, run:

```bash
branda-tests/run-generate-spas-input.sh
```

Now open **two** shells, one for the mock server and one to run the tests and see the results.  

> This is mean to be all red, as it is a way to test the different spas validators erroring out

In the first shell, run the mock server:

```bash
branda-tests/run-test-server.sh
```

In the second shell, execute the tests:

```bash
branda-tests/run-test-case.sh
```

Now you can examine how each of the test cases were handled by spas.


