const ENV = {
  isDevelopment: import.meta.env.DEV || import.meta.env.MODE === 'development',
  isProduction: import.meta.env.PROD || import.meta.env.MODE === 'production',
  mode: import.meta.env.MODE
};

const LOG_PREFIX = '[Club-Systems]';

const LogLevel = {
  DEBUG: 0,
  LOG: 1,
  INFO: 2,
  WARN: 3,
  ERROR: 4,
  NONE: 5
};

const currentLogLevel = ENV.isDevelopment ? LogLevel.DEBUG : LogLevel.ERROR;

function shouldLog(level) {
  if (ENV.isProduction) {
    return false;
  }
  return level >= currentLogLevel;
}

function formatLabel(label) {
  return label ? `[${label}]` : '';
}

function safeStringify(obj) {
  if (obj === undefined) return 'undefined';
  if (obj === null) return 'null';
  if (typeof obj === 'string') return obj;
  if (typeof obj === 'number' || typeof obj === 'boolean') return String(obj);
  if (obj instanceof Error) {
    return obj.message;
  }
  try {
    return JSON.stringify(obj, (key, value) => {
      if (key === 'password' || key === 'token' || key === 'authorization' || 
          key === 'Authorization' || key === 'csrf_token' || key === 'remember_token') {
        return '[REDACTED]';
      }
      return value;
    });
  } catch (e) {
    return '[Object]';
  }
}

export const logger = {
  debug: (label, ...args) => {
    if (!shouldLog(LogLevel.DEBUG)) return;
    const prefix = `${LOG_PREFIX} ${formatLabel(label)}`;
    console.debug(prefix, ...args);
  },

  log: (label, ...args) => {
    if (!shouldLog(LogLevel.LOG)) return;
    const prefix = `${LOG_PREFIX} ${formatLabel(label)}`;
    console.log(prefix, ...args);
  },

  info: (label, ...args) => {
    if (!shouldLog(LogLevel.INFO)) return;
    const prefix = `${LOG_PREFIX} ${formatLabel(label)}`;
    console.info(prefix, ...args);
  },

  warn: (label, ...args) => {
    if (!shouldLog(LogLevel.WARN)) return;
    const prefix = `${LOG_PREFIX} ${formatLabel(label)}`;
    console.warn(prefix, ...args);
  },

  error: (label, ...args) => {
    if (!shouldLog(LogLevel.ERROR)) return;
    const prefix = `${LOG_PREFIX} ${formatLabel(label)}`;
    console.error(prefix, ...args);
  },

  safe: (data) => safeStringify(data),

  group: (label) => {
    if (ENV.isProduction) return;
    console.group(`${LOG_PREFIX} ${formatLabel(label)}`);
  },

  groupEnd: () => {
    if (ENV.isProduction) return;
    console.groupEnd();
  },

  table: (data, columns) => {
    if (ENV.isProduction) return;
    console.table(data, columns);
  },

  time: (label) => {
    if (ENV.isProduction) return;
    console.time(`${LOG_PREFIX} ${label}`);
  },

  timeEnd: (label) => {
    if (ENV.isProduction) return;
    console.timeEnd(`${LOG_PREFIX} ${label}`);
  },

  assert: (condition, message) => {
    if (ENV.isProduction) return;
    console.assert(condition, `${LOG_PREFIX} ${message}`);
  }
};

export default logger;
