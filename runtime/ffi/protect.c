#include <sys/mman.h>
#include <stdint.h>

int protect(void* addr, size_t len) {
    size_t page   = (size_t)addr & ~(size_t)4095;
    size_t offset = (size_t)addr - page;
    size_t total  = len + offset;
    size_t aligned = ((total + 4095) / 4096) * 4096;
    return mprotect((void*)page, aligned, PROT_READ | PROT_WRITE | PROT_EXEC);
}
