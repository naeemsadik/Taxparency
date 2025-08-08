const hre = require("hardhat");

async function main() {
  const [deployer] = await hre.ethers.getSigners();

  console.log("Deploying contracts with the account:", deployer.address);
  console.log("Account balance:", (await deployer.getBalance()).toString());

  // Deploy TaxRegistry first
  console.log("\n🏛️ Deploying TaxRegistry...");
  const TaxRegistry = await hre.ethers.getContractFactory("TaxRegistry");
  const taxRegistry = await TaxRegistry.deploy();
  await taxRegistry.deployed();
  console.log("✅ TaxRegistry deployed to:", taxRegistry.address);

  // Deploy BudgetLedger with TaxRegistry address
  console.log("\n💰 Deploying BudgetLedger...");
  const BudgetLedger = await hre.ethers.getContractFactory("BudgetLedger");
  const budgetLedger = await BudgetLedger.deploy(taxRegistry.address);
  await budgetLedger.deployed();
  console.log("✅ BudgetLedger deployed to:", budgetLedger.address);

  // Deploy TenderContract
  console.log("\n📋 Deploying TenderContract...");
  const TenderContract = await hre.ethers.getContractFactory("TenderContract");
  const tenderContract = await TenderContract.deploy();
  await tenderContract.deployed();
  console.log("✅ TenderContract deployed to:", tenderContract.address);

  // Deploy ProjectTracker
  console.log("\n📊 Deploying ProjectTracker...");
  const ProjectTracker = await hre.ethers.getContractFactory("ProjectTracker");
  const projectTracker = await ProjectTracker.deploy();
  await projectTracker.deployed();
  console.log("✅ ProjectTracker deployed to:", projectTracker.address);

  // Setup some initial data for demonstration
  console.log("\n🔧 Setting up initial configuration...");
  
  // Add some validators to TaxRegistry (using deployer as example)
  console.log("Adding deployer as NBR validator...");
  // Deployer is already a validator by default
  
  // Add deployer as authorized creator/evaluator in TenderContract
  console.log("Configuring TenderContract permissions...");
  // Deployer is already authorized by default

  // Add deployer as authorized tracker in ProjectTracker
  console.log("Configuring ProjectTracker permissions...");
  // Deployer is already authorized by default

  // Add deployer as authorized deductor in BudgetLedger
  console.log("Configuring BudgetLedger permissions...");
  // Deployer is already authorized by default

  console.log("\n📄 Contract Addresses Summary:");
  console.log("===============================");
  console.log("TaxRegistry:     ", taxRegistry.address);
  console.log("BudgetLedger:    ", budgetLedger.address);
  console.log("TenderContract:  ", tenderContract.address);
  console.log("ProjectTracker:  ", projectTracker.address);

  console.log("\n🔗 Frontend Configuration:");
  console.log("==========================");
  console.log("Update the CONTRACT_ADDRESSES in frontend/js/web3-init.js with these addresses:");
  console.log(`
const CONTRACT_ADDRESSES = {
    TaxRegistry: '${taxRegistry.address}',
    TenderContract: '${tenderContract.address}',
    BudgetLedger: '${budgetLedger.address}',
    ProjectTracker: '${projectTracker.address}'
};
  `);

  // Save deployment info to a file
  const fs = require('fs');
  const deploymentInfo = {
    network: hre.network.name,
    deployer: deployer.address,
    contracts: {
      TaxRegistry: taxRegistry.address,
      BudgetLedger: budgetLedger.address,
      TenderContract: tenderContract.address,
      ProjectTracker: projectTracker.address
    },
    deployedAt: new Date().toISOString()
  };

  fs.writeFileSync(
    'deployment-info.json',
    JSON.stringify(deploymentInfo, null, 2)
  );

  console.log("\n💾 Deployment info saved to deployment-info.json");
  console.log("\n🚀 Deployment completed successfully!");
  console.log("\nNext steps:");
  console.log("1. Update the contract addresses in frontend/js/web3-init.js");
  console.log("2. Start a local HTTP server: npm start");
  console.log("3. Open http://localhost:8000 in your browser");
  console.log("4. Connect MetaMask to localhost:8545 (if using local network)");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error("❌ Deployment failed:", error);
    process.exit(1);
  });
