// Web3 Initialization and Connection Management
let provider = null;
let signer = null;
let userAddress = null;

// Contract addresses (to be updated after deployment)
const CONTRACT_ADDRESSES = {
    TaxRegistry: '0x5fbdb2315678afecb367f032d93f642f64180aa3', // Placeholder - update after deployment
    TenderContract: '0xe7f1725e7734ce288f8367e1bb143e90bb3f0512', // Placeholder - update after deployment
    BudgetLedger: '0x9fE46736679d2D9a65F0992F2272dE9f3c7fa6e0', // Placeholder - update after deployment
    ProjectTracker: '0xCf7Ed3AccA5a467e9e704C703E8D87F634fB0Fc9' // Placeholder - update after deployment
};

// Contract ABIs (simplified versions - full ABIs would be generated from compiled contracts)
const CONTRACT_ABIS = {
    TaxRegistry: [
        "function submitTaxReturn(uint256 _amount, string memory _documentHash) external",
        "function validateTaxSubmission(uint256 _submissionId, bool _approved) external",
        "function getTotalRevenue() external view returns (uint256, uint256)",
        "function getTaxSubmission(uint256 _submissionId) external view returns (tuple(uint256 id, address citizen, uint256 amount, uint256 timestamp, bool validated, string documentHash, address validatedBy))",
        "function getCitizenSubmissions(address _citizen) external view returns (uint256[] memory)",
        "function getPendingValidations() external view returns (uint256[] memory)",
        "event TaxSubmissionEvent(uint256 indexed submissionId, address indexed citizen, uint256 amount, uint256 timestamp, string documentHash)",
        "event TaxValidationEvent(uint256 indexed submissionId, address indexed validator, bool approved, uint256 timestamp)"
    ],
    
    TenderContract: [
        "function createTender(string memory _title, uint256 _amount, uint256 _deadline, uint8 _evaluationType, string memory _descriptionHash) external",
        "function submitProposal(uint256 _tenderId, uint256 _cost, string memory _technicalDocsHash) external",
        "function citizenVote(uint256 _tenderId, uint256 _vendorId) external",
        "function finalizeVendor(uint256 _tenderId, uint256 _vendorId) external",
        "function registerVendor(string memory _name, string memory _kycHash) external",
        "function getTender(uint256 _tenderId) external view returns (tuple(uint256 id, string title, uint256 amount, uint256 deadline, uint8 evaluationType, uint8 status, address createdBy, uint256 timestamp, string descriptionHash, uint256 selectedVendorId))",
        "function getActiveTenders() external view returns (uint256[] memory)",
        "function getTenderProposals(uint256 _tenderId) external view returns (uint256[] memory)",
        "function getProposal(uint256 _proposalId) external view returns (tuple(uint256 id, uint256 tenderId, uint256 vendorId, uint256 cost, string technicalDocsHash, uint256 timestamp, uint256 technicalScore, bool evaluated))",
        "function getVendor(uint256 _vendorId) external view returns (tuple(uint256 id, address vendorAddress, string name, string kycHash, bool verified, uint256 registrationTimestamp))",
        "function calculateScore(uint256 _tenderId) external view returns (uint256[] memory, uint256[] memory)",
        "function getVoteCount(uint256 _tenderId, uint256 _vendorId) external view returns (uint256)",
        "event TenderCreated(uint256 indexed tenderId, string title, uint256 amount, uint256 deadline, uint8 evaluationType, address indexed createdBy)",
        "event ProposalSubmitted(uint256 indexed proposalId, uint256 indexed tenderId, uint256 indexed vendorId, uint256 cost, string technicalDocsHash)",
        "event VoteCast(address indexed citizen, uint256 indexed tenderId, uint256 indexed vendorId, uint256 timestamp)"
    ],
    
    BudgetLedger: [
        "function getCurrentBalance() external view returns (uint256)",
        "function deductBudget(uint256 _projectId, uint256 _amount, string memory _description) external",
        "function getBudgetSummary() external view returns (uint256, uint256, uint256)",
        "function getAllDeductions() external view returns (tuple(uint256 projectId, uint256 amount, uint256 timestamp, string description, address authorizedBy)[] memory)",
        "event BudgetDeduction(uint256 indexed entryId, uint256 indexed projectId, uint256 amount, address indexed authorizedBy, uint256 timestamp, string description)"
    ],
    
    ProjectTracker: [
        "function createProject(uint256 _tenderId, uint256 _vendorId, uint256 _budget, uint256 _expectedEndDate, string memory _title, string memory _descriptionHash) external",
        "function postUpdate(uint256 _projectId, uint8 _updateType, string memory _dataHash, string memory _description) external",
        "function flagIssue(uint256 _projectId, string memory _reason, string memory _evidenceHash) external",
        "function getActiveProjects() external view returns (uint256[] memory)",
        "function getProject(uint256 _projectId) external view returns (tuple(uint256 id, uint256 tenderId, uint256 vendorId, uint256 budget, uint256 startDate, uint256 expectedEndDate, uint8 status, string title, string descriptionHash))",
        "function getProjectUpdates(uint256 _projectId) external view returns (uint256[] memory)",
        "function getProjectUpdate(uint256 _updateId) external view returns (tuple(uint256 id, uint256 projectId, uint256 vendorId, uint8 updateType, string dataHash, string description, uint256 timestamp, bool verified, address verifiedBy))",
        "event ProjectCreated(uint256 indexed projectId, uint256 indexed tenderId, uint256 indexed vendorId, uint256 budget, string title)",
        "event UpdatePosted(uint256 indexed updateId, uint256 indexed projectId, uint256 indexed vendorId, uint8 updateType, string description, string dataHash)"
    ]
};

// Initialize Web3 connection
async function initializeWeb3() {
    try {
        // Check if MetaMask is installed
        if (typeof window.ethereum === 'undefined') {
            throw new Error('MetaMask is not installed. Please install MetaMask to use this application.');
        }

        // Create provider
        provider = new ethers.providers.Web3Provider(window.ethereum);
        
        // Request account access
        await window.ethereum.request({ method: 'eth_requestAccounts' });
        
        // Get signer
        signer = provider.getSigner();
        userAddress = await signer.getAddress();
        
        // Update connection status
        updateConnectionStatus('connected', `Connected: ${userAddress.substring(0, 6)}...${userAddress.substring(38)}`);
        
        // Update blockchain info
        updateBlockchainInfo();
        
        // Listen for account changes
        window.ethereum.on('accountsChanged', (accounts) => {
            if (accounts.length > 0) {
                location.reload(); // Simple way to handle account changes
            } else {
                updateConnectionStatus('disconnected', 'No account connected');
            }
        });
        
        // Listen for network changes
        window.ethereum.on('chainChanged', () => {
            location.reload(); // Simple way to handle network changes
        });
        
        console.log('Web3 initialized successfully');
        return true;
        
    } catch (error) {
        console.error('Error initializing Web3:', error);
        updateConnectionStatus('error', error.message);
        throw error;
    }
}

// Update connection status in UI
function updateConnectionStatus(status, message) {
    const statusIndicator = document.getElementById('blockchainStatus');
    if (!statusIndicator) return;
    
    const statusDot = statusIndicator.querySelector('.status-dot');
    const statusText = statusIndicator.querySelector('.status-text');
    
    // Remove existing status classes
    statusDot.classList.remove('connected', 'connecting', 'disconnected', 'error');
    
    // Add new status class
    statusDot.classList.add(status);
    statusText.textContent = message;
}

// Update blockchain information
async function updateBlockchainInfo() {
    try {
        if (!provider) return;
        
        const network = await provider.getNetwork();
        const blockNumber = await provider.getBlockNumber();
        
        // Update network name
        const networkElement = document.getElementById('networkName');
        if (networkElement) {
            networkElement.textContent = network.name || `Chain ID: ${network.chainId}`;
        }
        
        // Update current block
        const blockElement = document.getElementById('currentBlock');
        if (blockElement) {
            blockElement.textContent = blockNumber.toString();
        }
        
    } catch (error) {
        console.error('Error updating blockchain info:', error);
    }
}

// Get contract instance
function getContract(contractName) {
    if (!CONTRACT_ADDRESSES[contractName] || !CONTRACT_ABIS[contractName]) {
        throw new Error(`Contract ${contractName} not found`);
    }
    
    if (!signer) {
        throw new Error('Web3 not initialized. Please connect your wallet.');
    }
    
    return new ethers.Contract(
        CONTRACT_ADDRESSES[contractName],
        CONTRACT_ABIS[contractName],
        signer
    );
}

// Utility functions
function formatEther(value) {
    return ethers.utils.formatEther(value);
}

function parseEther(value) {
    return ethers.utils.parseEther(value.toString());
}

function formatAddress(address) {
    return `${address.substring(0, 6)}...${address.substring(38)}`;
}

function formatTimestamp(timestamp) {
    return new Date(timestamp * 1000).toLocaleString();
}

// Error handling utility
function handleContractError(error, context = '') {
    console.error(`Contract error ${context}:`, error);
    
    let message = 'An error occurred';
    
    if (error.message.includes('user rejected')) {
        message = 'Transaction was rejected by user';
    } else if (error.message.includes('insufficient funds')) {
        message = 'Insufficient funds for transaction';
    } else if (error.message.includes('revert')) {
        // Extract revert reason if available
        const revertMatch = error.message.match(/revert (.+?)"/);
        if (revertMatch) {
            message = `Transaction failed: ${revertMatch[1]}`;
        } else {
            message = 'Transaction failed';
        }
    }
    
    return message;
}

// Transaction waiting utility
async function waitForTransaction(tx, description = 'Transaction') {
    try {
        console.log(`${description} submitted:`, tx.hash);
        
        // Show loading state
        const receipt = await tx.wait();
        console.log(`${description} confirmed:`, receipt.transactionHash);
        
        return receipt;
    } catch (error) {
        console.error(`${description} failed:`, error);
        throw error;
    }
}

// Export functions for use in other files
window.initializeWeb3 = initializeWeb3;
window.getContract = getContract;
window.formatEther = formatEther;
window.parseEther = parseEther;
window.formatAddress = formatAddress;
window.formatTimestamp = formatTimestamp;
window.handleContractError = handleContractError;
window.waitForTransaction = waitForTransaction;
window.updateConnectionStatus = updateConnectionStatus;
