module.exports = {
  // Server configuration
  server: {
    host: "0.0.0.0",
    port: 8545,
  },
  
  // Chain configuration
  chain: {
    chainId: 1337,
    networkId: 1337,
    time: new Date(),
    hardfork: "istanbul",
  },
  
  // Database configuration
  database: {
    dbPath: "./ganache_db",
  },
  
  // Logging
  logging: {
    debug: false,
    verbose: true,
  },
  
  // Miner configuration
  miner: {
    blockTime: 1, // Automatically mine blocks every 1 second
    defaultGasPrice: "0x77359400", // 2 gwei
    callGasLimit: "0x1fffffffffffff",
    instamine: "strict",
  },
  
  // Wallet configuration
  wallet: {
    deterministic: true,
    seed: "taxparency_blockchain_demo",
    mnemonic: "abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about",
    totalAccounts: 10,
    defaultBalance: "1000000000000000000000", // 1000 ETH
  },
  
  // Fork configuration (if needed)
  fork: {
    // url: "https://mainnet.infura.io/v3/YOUR_PROJECT_ID",
    // blockNumber: 12345678,
  },
};
