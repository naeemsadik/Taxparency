# 🔍 Taxparency

**Blockchain-Powered Government Transparency Platform**

Taxparency is a decentralized platform built on Ethereum that enables transparent government tax handling, citizen validation of tenders, and vendor accountability—all without using cryptocurrency or tokens.

## 🌟 Features

### 🏛️ **Government Transparency**
- **Immutable Tax Records**: All tax submissions stored permanently on blockchain
- **Real-time Budget Tracking**: Live monitoring of government revenue and spending
- **Transparent Procurement**: Open tender process with citizen validation
- **Audit Trail**: Complete history of all government financial activities

### 👥 **Citizen Participation**
- Submit tax returns with document verification
- Vote on government tender selections (L1/QCBS)
- Monitor how tax money is being spent
- View real-time government revenue statistics

### 🏢 **Vendor Accountability**
- Register and complete KYC verification
- Submit competitive proposals for government tenders
- Post immutable project updates and milestones
- Maintain transparent project delivery records

### 🏛️ **Government Officials**
- **NBR (Tax Authority)**: Validate tax submissions and monitor revenue
- **BPPA (Procurement Authority)**: Create tenders, select vendors, track projects

## 🛠️ Tech Stack

### Backend (Blockchain)
- **Ethereum** (Local testnet using Hardhat)
- **Solidity** smart contracts
- **Ethers.js** for blockchain interaction
- **IPFS** for document storage (references)

### Frontend
- **HTML5/CSS3/JavaScript** (Vanilla)
- **Responsive Design** with modern UI
- **Web3 Integration** via MetaMask
- **Real-time Data** from smart contracts

## 📋 Smart Contracts

### 1. **TaxRegistry.sol**
- Handles tax return submissions
- Manages validation by NBR officials
- Tracks total and validated revenue

### 2. **TenderContract.sol**
- Creates and manages government tenders
- Handles vendor registration and proposals
- Implements L1 (Lowest) and QCBS (Quality & Cost Based) evaluation
- Manages citizen voting on vendor selection

### 3. **BudgetLedger.sol**
- Tracks government budget and deductions
- Automatically deducts approved project budgets
- Maintains transparent spending records

### 4. **ProjectTracker.sol**
- Monitors project progress and milestones
- Handles vendor updates and issue reporting
- Maintains project status and completion records

## 🚀 Quick Start

### Prerequisites
- **Node.js** (v16.0.0 or higher)
- **npm** (v8.0.0 or higher)
- **MetaMask** browser extension
- **Git** (for version control)

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd taxparency
```

2. **Install dependencies**
```bash
npm install
```

3. **Start local Ethereum network**
```bash
# Terminal 1 - Start Hardhat node
npx hardhat node
```

4. **Deploy smart contracts**
```bash
# Terminal 2 - Deploy contracts
npm run deploy:local
```

5. **Update contract addresses**
   - Copy the deployed contract addresses from the terminal output
   - Update `frontend/js/web3-init.js` with the new addresses

6. **Start the web server**
```bash
npm start
# Opens http://localhost:8000
```

7. **Configure MetaMask**
   - Add localhost network: `http://127.0.0.1:8545`
   - Chain ID: `1337`
   - Import one of the test accounts from Hardhat node output

## 📖 User Guide

### 🏠 **Main Dashboard**
Visit `http://localhost:8000` to access the platform landing page with:
- Live revenue statistics
- Active tender count
- Role selection (Citizen, Vendor, NBR, BPPA)

### 👤 **Citizen Dashboard** (`citizen.html`)
- **Submit Tax Returns**: Upload tax amount and supporting documents
- **View Tax History**: Track all your submissions and validation status  
- **Vote on Tenders**: Participate in government procurement decisions
- **Monitor Spending**: See how government uses tax revenue

### 🏢 **Vendor Dashboard** (`vendor.html`)
- **Register & KYC**: Complete vendor registration with verification
- **Browse Tenders**: View active government procurement opportunities
- **Submit Proposals**: Provide technical and financial proposals
- **Track Projects**: Update project progress and milestones

### 🏛️ **NBR Dashboard** (`nbr.html`)
- **Validate Tax Returns**: Review and approve citizen submissions
- **Revenue Monitoring**: Track total tax collection and validation
- **Audit Reports**: Generate financial audit trails

### 📋 **BPPA Dashboard** (`bppa.html`)
- **Create Tenders**: Post new government procurement opportunities
- **Manage Evaluation**: Set L1 or QCBS evaluation criteria
- **Select Vendors**: Finalize vendor selection based on citizen votes
- **Track Projects**: Monitor project delivery and budget usage

## 🔧 System Architecture

### Evaluation Systems

#### **L1 (Lowest One)**
- Automatically selects lowest cost proposal
- Citizens vote after viewing all bids
- Transparent cost comparison

#### **QCBS (Quality & Cost Based Selection)**
- Weighted scoring: Technical (70%) + Cost (30%)
- Technical evaluation by authorized officials
- Citizens see combined scores before voting

### Data Flow
1. **Tax Collection**: Citizens → TaxRegistry → NBR Validation → BudgetLedger
2. **Tender Process**: BPPA → TenderContract → Vendor Proposals → Citizen Voting → Selection
3. **Project Execution**: Selected Vendor → ProjectTracker → Budget Deduction → Progress Updates

## 🛡️ Security Features

- **Access Control**: Role-based permissions for all contract functions
- **Immutable Records**: All transactions permanently stored on blockchain
- **Multi-party Validation**: Tax validation requires NBR official approval
- **Transparent Voting**: Citizen votes are public and verifiable
- **Audit Trail**: Complete transaction history for all operations

## 🔍 Testing

### Run Contract Tests
```bash
npm test
```

### Manual Testing
1. Deploy to local network
2. Use different MetaMask accounts for different roles
3. Test complete workflow: Tax submission → Validation → Tender creation → Voting → Project tracking

## 📁 Project Structure

```
taxparency/
├── contracts/              # Solidity smart contracts
│   ├── TaxRegistry.sol
│   ├── TenderContract.sol
│   ├── BudgetLedger.sol
│   └── ProjectTracker.sol
├── frontend/               # Web frontend
│   ├── index.html         # Landing page
│   ├── citizen.html       # Citizen dashboard
│   ├── vendor.html        # Vendor dashboard  
│   ├── nbr.html          # NBR dashboard
│   ├── bppa.html         # BPPA dashboard
│   ├── css/
│   │   └── styles.css    # Platform styles
│   └── js/
│       ├── web3-init.js  # Web3 connection
│       └── contract-calls.js # Smart contract interactions
├── scripts/               # Deployment scripts
│   └── deploy.js
├── utils/                 # Utility functions
├── hardhat.config.js     # Hardhat configuration
├── package.json          # Dependencies
└── README.md             # This file
```

## 🚀 Deployment

### Local Development
```bash
npm run node          # Start Hardhat network
npm run deploy:local  # Deploy to localhost
npm start             # Start web server
```

### Testnet Deployment
1. Configure testnet RPC URLs in `hardhat.config.js`
2. Set private key in environment variables
3. Deploy: `npm run deploy:testnet`

## 🤝 Contributing

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

## 📄 License

This project is licensed under the MIT License. See `LICENSE` file for details.

## 🆘 Support

- **Documentation**: Check this README and inline code comments
- **Issues**: Report bugs or request features via GitHub Issues
- **Community**: Join our community discussions

## 🎯 Roadmap

### Phase 1 (Current)
- ✅ Core smart contracts
- ✅ Basic frontend interface
- ✅ Local development setup

### Phase 2 (Planned)
- [ ] IPFS integration for document storage
- [ ] Advanced reporting and analytics
- [ ] Mobile-responsive design improvements
- [ ] Multi-language support

### Phase 3 (Future)
- [ ] Integration with real government systems
- [ ] Advanced tender evaluation algorithms
- [ ] Automated compliance checking
- [ ] API for third-party integrations

## ⚠️ Important Notes

- **Development Only**: This is a demonstration platform, not for production use
- **No Cryptocurrency**: Platform uses ETH only for gas fees, not as currency
- **Testnet First**: Always test on testnet before any mainnet deployment
- **Security Audit**: Requires professional security audit before production use

## 📈 Key Metrics

The platform tracks:
- Total tax revenue collected
- Validation rates by NBR officials  
- Tender participation rates
- Project completion statistics
- Citizen engagement levels

---

**Built with ❤️ for Government Transparency**

*Empowering citizens, enabling transparency, ensuring accountability.*
