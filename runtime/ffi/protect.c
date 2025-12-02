#include <sys/mman.h>
#include <stdint.h>

int protect(void* addr, size_t len) {
    size_t page = (size_t) addr & ~(size_t) 4095;
    return mprotect((void*) page, len, PROT_READ | PROT_WRITE | PROT_EXEC);
}


