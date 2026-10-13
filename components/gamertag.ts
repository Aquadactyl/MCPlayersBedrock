export const validGamertag = (name: string) =>
  name === name.trim() &&
  /^[\p{L}\p{M}\p{N}_ .#-]{1,32}$/u.test(name) &&
  /[\p{L}\p{N}_]/u.test(name);
