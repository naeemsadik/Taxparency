const { ethers } = require("hardhat");

async function main() {
  console.log("Starting deployment of Taxparency smart contracts...");

  // Get the deployer account
  const [deployer] = await ethers.getSigners();
  console.log("Deploying contracts with account:", deployer.address);
  console.log("Account balance:", (await ethers.provider.getBalance(deployer.address)).toString());

  // Deploy TaxReturnRegistry (Private Blockchain Contract)
  console.log("\n--- Deploying TaxReturnRegistry Contract ---");
  const TaxReturnRegistry = await ethers.getContractFactory("TaxReturnRegistry");
  const taxReturnRegistry = await TaxReturnRegistry.deploy();
  await taxReturnRegistry.waitForDeployment();
  
  const taxReturnAddress = await taxReturnRegistry.getAddress();
  console.log("TaxReturnRegistry deployed to:", taxReturnAddress);

  // Deploy ProcurementVoting (Public Blockchain Contract)
  console.log("\n--- Deploying ProcurementVoting Contract ---");
  const ProcurementVoting = await ethers.getContractFactory("ProcurementVoting");
  const procurementVoting = await ProcurementVoting.deploy();
  await procurementVoting.waitForDeployment();
  
  const procurementAddress = await procurementVoting.getAddress();
  console.log("ProcurementVoting deployed to:", procurementAddress);

  // Log contract addresses for easy reference
  console.log("\n=== DEPLOYMENT SUMMARY ===");
  console.log("TaxReturnRegistry Address:", taxReturnAddress);
  console.log("ProcurementVoting Address:", procurementAddress);
  console.log("Deployer Address:", deployer.address);
  
  // Save deployment info to a JSON file
  const deploymentInfo = {
    network: "localhost", // or process.env.HARDHAT_NETWORK
    deployer: deployer.address,
    contracts: {
      TaxReturnRegistry: taxReturnAddress,
      ProcurementVoting: procurementAddress
    },
    deployedAt: new Date().toISOString()
  };

  console.log("\nDeployment completed successfully!");
  console.log("Save this information for your Laravel backend configuration:");
  console.log(JSON.stringify(deploymentInfo, null, 2));
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error("Deployment failed:", error);
    process.exit(1);
  });
