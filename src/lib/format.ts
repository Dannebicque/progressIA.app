// French pluralization: plural only when |n| >= 2 (0 and 1 stay singular).
export function pluralize(n: number, singular: string, pluralForm?: string): string {
  const word = Math.abs(n) >= 2 ? (pluralForm ?? `${singular}s`) : singular
  return `${n} ${word}`
}
