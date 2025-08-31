const { ethers } = require("hardhat");

async function main() {
  console.log("Starting deployment of Taxparency smart contracts...");
  console.log("Deploying enhanced system according to PRD v1.0...");

  // Get the deployer account
  const [deployer] = await ethers.getSigners();
  console.log("Deploying contracts with account:", deployer.address);
  console.log("Account balance:", (await ethers.provider.getBalance(deployer.address)).toString());

  // 1. Deploy NationalLedger (Public Blockchain - for transparency)
  console.log("\n--- Deploying NationalLedger Contract ---");
  const NationalLedger = await ethers.getContractFactory("NationalLedger");
  const nationalLedger = await NationalLedger.deploy();
  await nationalLedger.deployTransaction.wait();
  
  const nationalLedgerAddress = nationalLedger.address;
  console.log("NationalLedger deployed to:", nationalLedgerAddress);

  // 2. Deploy TaxReturnRegistry (Private Blockchain Contract)
  console.log("\n--- Deploying TaxReturnRegistry Contract ---");
  const TaxReturnRegistry = await ethers.getContractFactory("TaxReturnRegistry");
  const taxReturnRegistry = await TaxReturnRegistry.deploy();
  await taxReturnRegistry.deployed();
  
  const taxReturnAddress = taxReturnRegistry.address;
  console.log("TaxReturnRegistry deployed to:", taxReturnAddress);

  // 3. Deploy Procurement (Private Blockchain - for sensitive bidding)
  console.log("\n--- Deploying Procurement Contract ---");
  const Procurement = await ethers.getContractFactory("Procurement");
  const procurement = await Procurement.deploy();
  await procurement.deployed();
  
  const procurementAddress = procurement.address;
  console.log("Procurement deployed to:", procurementAddress);

  // 4. Deploy ProcurementVoting (Public Blockchain Contract)
  console.log("\n--- Deploying ProcurementVoting Contract ---");
  const ProcurementVoting = await ethers.getContractFactory("ProcurementVoting");
  const procurementVoting = await ProcurementVoting.deploy();
  await procurementVoting.deployed();
  
  const procurementVotingAddress = procurementVoting.address;
  console.log("ProcurementVoting deployed to:", procurementVotingAddress);

  // 5. Deploy FundRequest (Private Blockchain - for sensitive approvals)
  console.log("\n--- Deploying FundRequest Contract ---");
  const FundRequest = await ethers.getContractFactory("FundRequest");
  const fundRequest = await FundRequest.deploy();
  await fundRequest.deployed();
  
  const fundRequestAddress = fundRequest.address;
  console.log("FundRequest deployed to:", fundRequestAddress);

  // Setup contract integrations
  console.log("\n--- Setting up Contract Integrations ---");
  
  // Grant TaxReturnRegistry permission to add revenue to NationalLedger
  console.log("Granting TaxReturnRegistry revenue adder permission...");
  await nationalLedger.grantRevenueAdder(taxReturnAddress);
  
  // Grant Procurement permission to add expenses to NationalLedger
  console.log("Granting Procurement expense adder permission...");
  await nationalLedger.grantExpenseAdder(procurementAddress);
  
  // Grant FundRequest permission to add expenses to NationalLedger
  console.log("Granting FundRequest expense adder permission...");
  await nationalLedger.grantExpenseAdder(fundRequestAddress);
  
  // Set NationalLedger address in TaxReturnRegistry
  console.log("Linking TaxReturnRegistry to NationalLedger...");
  await taxReturnRegistry.setNationalLedger(nationalLedgerAddress);

  // Log contract addresses for easy reference
  console.log("\n=== DEPLOYMENT SUMMARY ===");
  console.log("\n🏛️ Public Blockchain Contracts (Transparency):");
  console.log("   NationalLedger Address:", nationalLedgerAddress);
  console.log("   ProcurementVoting Address:", procurementVotingAddress);
  console.log("\n🔒 Private Blockchain Contracts (Sensitive Data):");
  console.log("   TaxReturnRegistry Address:", taxReturnAddress);
  console.log("   Procurement Address:", procurementAddress);
  console.log("   FundRequest Address:", fundRequestAddress);
  console.log("\n👤 System Admin:");
  console.log("   Deployer Address:", deployer.address);
  
  // Save deployment info to a JSON file
  const deploymentInfo = {
    network: "localhost", // or process.env.HARDHAT_NETWORK
    deployer: deployer.address,
    publicBlockchain: {
      description: "Contracts deployed on public blockchain for transparency",
      contracts: {
        NationalLedger: nationalLedgerAddress,
        ProcurementVoting: procurementVotingAddress
      }
    },
    privateBlockchain: {
      description: "Contracts deployed on private blockchain for sensitive operations",
      contracts: {
        TaxReturnRegistry: taxReturnAddress,
        Procurement: procurementAddress,
        FundRequest: fundRequestAddress
      }
    },
    integrations: {
      description: "Contract integrations setup",
      nationalLedgerRevenueAdders: [taxReturnAddress],
      nationalLedgerExpenseAdders: [procurementAddress, fundRequestAddress],
      taxReturnRegistryLinkedToNationalLedger: true
    },
    deployedAt: new Date().toISOString(),
    prdVersion: "1.0",
    systemFeatures: [
      "Tax Return Management with NBR Validation",
      "Procurement Lifecycle Management (L1 & QCBS)",
      "Public Voting on Shortlisted Bids",
      "National Revenue & Expense Ledger",
      "Vendor Fund Request & BPPA Approval System",
      "Hybrid Blockchain Architecture (Public + Private)"
    ]
  };

  console.log("\n✅ Deployment completed successfully!");
  console.log("\n📋 Configuration for Laravel Backend:");
  console.log(JSON.stringify(deploymentInfo, null, 2));
  
  console.log("\n🔗 Next Steps:");
  console.log("1. Update your Laravel .env file with these contract addresses");
  console.log("2. Configure private/public blockchain RPC URLs");
  console.log("3. Set up IPFS node for PDF storage");
  console.log("4. Register initial NBR officers, BPPA officers, and vendors");
  console.log("5. Test the complete workflow from tax submission to procurement voting");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error("Deployment failed:", error);
    process.exit(1);
  });
