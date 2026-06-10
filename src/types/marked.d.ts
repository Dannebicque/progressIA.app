// marked@5 ships no type declarations; minimal ambient shim.
declare module 'marked' {
  export function parse(src: string, options?: Record<string, unknown>): string
  export function setOptions(options: Record<string, unknown>): void
  export const marked: {
    parse(src: string, options?: Record<string, unknown>): string
    setOptions(options: Record<string, unknown>): void
  }
}
