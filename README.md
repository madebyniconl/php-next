# ULTRA V2 – Zero-Knowledge Crypto VM for PHP

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-≥8.3-8892bf.svg)](https://php.net)

Run encrypted PHP **without** `eval`, **without** tmpfiles, **without** leaking keys.

## Quick Start – Zero-Knowledge Benchmark
```bash
docker run --rm phpnext/ultra-bench
You will see the ULTRA2 container execute and exit successfully – no source, no keys, no trust required.
What You Get
Pure-PHP constant-time crypto VM
Timing-safe AES-256-CTR + HMAC-SHA-256 pipeline
FFI-based memory isolation (mprotect)
Deterministic containers (same input = same blob)
Post-quantum ready – Kyber/Dilithium in roadmap
MIT licensed – use, fork, audit.
Local Demo
bash
Copy
git clone https://github.com/madebyniconl/php-next
cd php-next
php examples/run.php   # runs dist/hello.ultra2
Build Your Own (optional)
bash
Copy
# compile memory helper
gcc -shared -fPIC -o runtime/ffi/protect.so runtime/ffi/protect.c

# create a container (requires private compiler)
# php compiler/build.php myscript.php → dist/myscript.ultra2
Research Status
Submitted for USENIX / Black Hat review
Formal verification in progress
Public zero-knowledge benchmark for independent validation
Files
Table
Copy
Path	Purpose	License
runtime/loader.php	decrypt + verify + execute	MIT
runtime/ffi/protect.c	memory-protection helper	MIT
examples/	demo scripts & blobs	MIT
benchmark/	public ZK-proof container	MIT
Contact
Research & press: info@madebynico.nl
No php-next e-mail addresses are used or published.
