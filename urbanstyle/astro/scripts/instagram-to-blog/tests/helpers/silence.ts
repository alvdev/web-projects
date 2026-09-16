const noop = (): void => {};
console.log = noop as typeof console.log;
console.warn = noop as typeof console.warn;
console.error = noop as typeof console.error;
