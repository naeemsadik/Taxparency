// Contract interaction functions

// ============================================================================
// TAX REGISTRY FUNCTIONS
// ============================================================================

async function submitTaxReturn(amount, documentHash) {
    try {
        const contract = getContract('TaxRegistry');
        const amountWei = parseEther(amount);
        
        const tx = await contract.submitTaxReturn(amountWei, documentHash);
        return await waitForTransaction(tx, 'Tax submission');
    } catch (error) {
        throw new Error(handleContractError(error, 'submitting tax return'));
    }
}

async function validateTaxSubmission(submissionId, approved) {
    try {
        const contract = getContract('TaxRegistry');
        
        const tx = await contract.validateTaxSubmission(submissionId, approved);
        return await waitForTransaction(tx, 'Tax validation');
    } catch (error) {
        throw new Error(handleContractError(error, 'validating tax submission'));
    }
}

async function getTotalRevenue() {
    try {
        const contract = getContract('TaxRegistry');
        const [total, validated] = await contract.getTotalRevenue();
        
        return {
            total: total,
            validated: validated
        };
    } catch (error) {
        throw new Error(handleContractError(error, 'getting total revenue'));
    }
}

async function getTaxSubmission(submissionId) {
    try {
        const contract = getContract('TaxRegistry');
        return await contract.getTaxSubmission(submissionId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting tax submission'));
    }
}

async function getCitizenSubmissions(citizenAddress) {
    try {
        const contract = getContract('TaxRegistry');
        return await contract.getCitizenSubmissions(citizenAddress);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting citizen submissions'));
    }
}

async function getPendingValidations() {
    try {
        const contract = getContract('TaxRegistry');
        return await contract.getPendingValidations();
    } catch (error) {
        throw new Error(handleContractError(error, 'getting pending validations'));
    }
}

// ============================================================================
// TENDER CONTRACT FUNCTIONS
// ============================================================================

async function createTender(title, amount, deadline, evaluationType, descriptionHash) {
    try {
        const contract = getContract('TenderContract');
        const amountWei = parseEther(amount);
        const deadlineTimestamp = Math.floor(new Date(deadline).getTime() / 1000);
        
        const tx = await contract.createTender(title, amountWei, deadlineTimestamp, evaluationType, descriptionHash);
        return await waitForTransaction(tx, 'Tender creation');
    } catch (error) {
        throw new Error(handleContractError(error, 'creating tender'));
    }
}

async function registerVendor(name, kycHash) {
    try {
        const contract = getContract('TenderContract');
        
        const tx = await contract.registerVendor(name, kycHash);
        return await waitForTransaction(tx, 'Vendor registration');
    } catch (error) {
        throw new Error(handleContractError(error, 'registering vendor'));
    }
}

async function submitProposal(tenderId, cost, technicalDocsHash) {
    try {
        const contract = getContract('TenderContract');
        const costWei = parseEther(cost);
        
        const tx = await contract.submitProposal(tenderId, costWei, technicalDocsHash);
        return await waitForTransaction(tx, 'Proposal submission');
    } catch (error) {
        throw new Error(handleContractError(error, 'submitting proposal'));
    }
}

async function citizenVote(tenderId, vendorId) {
    try {
        const contract = getContract('TenderContract');
        
        const tx = await contract.citizenVote(tenderId, vendorId);
        return await waitForTransaction(tx, 'Citizen vote');
    } catch (error) {
        throw new Error(handleContractError(error, 'casting vote'));
    }
}

async function finalizeVendor(tenderId, vendorId) {
    try {
        const contract = getContract('TenderContract');
        
        const tx = await contract.finalizeVendor(tenderId, vendorId);
        return await waitForTransaction(tx, 'Vendor finalization');
    } catch (error) {
        throw new Error(handleContractError(error, 'finalizing vendor'));
    }
}

async function getActiveTenders() {
    try {
        const contract = getContract('TenderContract');
        return await contract.getActiveTenders();
    } catch (error) {
        throw new Error(handleContractError(error, 'getting active tenders'));
    }
}

async function getTender(tenderId) {
    try {
        const contract = getContract('TenderContract');
        return await contract.getTender(tenderId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting tender'));
    }
}

async function getTenderProposals(tenderId) {
    try {
        const contract = getContract('TenderContract');
        return await contract.getTenderProposals(tenderId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting tender proposals'));
    }
}

async function getProposal(proposalId) {
    try {
        const contract = getContract('TenderContract');
        return await contract.getProposal(proposalId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting proposal'));
    }
}

async function getVendor(vendorId) {
    try {
        const contract = getContract('TenderContract');
        return await contract.getVendor(vendorId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting vendor'));
    }
}

async function calculateScore(tenderId) {
    try {
        const contract = getContract('TenderContract');
        return await contract.calculateScore(tenderId);
    } catch (error) {
        throw new Error(handleContractError(error, 'calculating scores'));
    }
}

async function getVoteCount(tenderId, vendorId) {
    try {
        const contract = getContract('TenderContract');
        return await contract.getVoteCount(tenderId, vendorId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting vote count'));
    }
}

// ============================================================================
// BUDGET LEDGER FUNCTIONS
// ============================================================================

async function getCurrentBalance() {
    try {
        const contract = getContract('BudgetLedger');
        return await contract.getCurrentBalance();
    } catch (error) {
        throw new Error(handleContractError(error, 'getting current balance'));
    }
}

async function deductBudget(projectId, amount, description) {
    try {
        const contract = getContract('BudgetLedger');
        const amountWei = parseEther(amount);
        
        const tx = await contract.deductBudget(projectId, amountWei, description);
        return await waitForTransaction(tx, 'Budget deduction');
    } catch (error) {
        throw new Error(handleContractError(error, 'deducting budget'));
    }
}

async function getBudgetSummary() {
    try {
        const contract = getContract('BudgetLedger');
        const [validatedRevenue, totalDeducted, currentBalance] = await contract.getBudgetSummary();
        
        return {
            validatedRevenue: validatedRevenue,
            totalDeducted: totalDeducted,
            currentBalance: currentBalance
        };
    } catch (error) {
        throw new Error(handleContractError(error, 'getting budget summary'));
    }
}

async function getAllDeductions() {
    try {
        const contract = getContract('BudgetLedger');
        return await contract.getAllDeductions();
    } catch (error) {
        throw new Error(handleContractError(error, 'getting all deductions'));
    }
}

// ============================================================================
// PROJECT TRACKER FUNCTIONS
// ============================================================================

async function createProject(tenderId, vendorId, budget, expectedEndDate, title, descriptionHash) {
    try {
        const contract = getContract('ProjectTracker');
        const budgetWei = parseEther(budget);
        const endDateTimestamp = Math.floor(new Date(expectedEndDate).getTime() / 1000);
        
        const tx = await contract.createProject(tenderId, vendorId, budgetWei, endDateTimestamp, title, descriptionHash);
        return await waitForTransaction(tx, 'Project creation');
    } catch (error) {
        throw new Error(handleContractError(error, 'creating project'));
    }
}

async function postUpdate(projectId, updateType, dataHash, description) {
    try {
        const contract = getContract('ProjectTracker');
        
        const tx = await contract.postUpdate(projectId, updateType, dataHash, description);
        return await waitForTransaction(tx, 'Project update');
    } catch (error) {
        throw new Error(handleContractError(error, 'posting project update'));
    }
}

async function flagIssue(projectId, reason, evidenceHash) {
    try {
        const contract = getContract('ProjectTracker');
        
        const tx = await contract.flagIssue(projectId, reason, evidenceHash);
        return await waitForTransaction(tx, 'Issue reporting');
    } catch (error) {
        throw new Error(handleContractError(error, 'flagging issue'));
    }
}

async function getActiveProjects() {
    try {
        const contract = getContract('ProjectTracker');
        return await contract.getActiveProjects();
    } catch (error) {
        throw new Error(handleContractError(error, 'getting active projects'));
    }
}

async function getProject(projectId) {
    try {
        const contract = getContract('ProjectTracker');
        return await contract.getProject(projectId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting project'));
    }
}

async function getProjectUpdates(projectId) {
    try {
        const contract = getContract('ProjectTracker');
        return await contract.getProjectUpdates(projectId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting project updates'));
    }
}

async function getProjectUpdate(updateId) {
    try {
        const contract = getContract('ProjectTracker');
        return await contract.getProjectUpdate(updateId);
    } catch (error) {
        throw new Error(handleContractError(error, 'getting project update'));
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

// Format tender status
function formatTenderStatus(status) {
    const statusMap = {
        0: 'Open',
        1: 'Closed',
        2: 'Finalized'
    };
    return statusMap[status] || 'Unknown';
}

// Format evaluation type
function formatEvaluationType(type) {
    const typeMap = {
        0: 'L1 (Lowest)',
        1: 'QCBS (Quality & Cost)'
    };
    return typeMap[type] || 'Unknown';
}

// Format project status
function formatProjectStatus(status) {
    const statusMap = {
        0: 'Active',
        1: 'Completed',
        2: 'Delayed',
        3: 'Flagged'
    };
    return statusMap[status] || 'Unknown';
}

// Format update type
function formatUpdateType(type) {
    const typeMap = {
        0: 'Milestone',
        1: 'Receipt',
        2: 'Justification',
        3: 'Issue'
    };
    return typeMap[type] || 'Unknown';
}

// Generate IPFS hash placeholder (in real implementation, this would upload to IPFS)
function generateIPFSHash(data) {
    // This is a placeholder. In a real implementation, you would:
    // 1. Upload the data/file to IPFS
    // 2. Return the actual IPFS hash
    return 'Qm' + Math.random().toString(36).substring(2, 46);
}

// Load tender data with proposals and voting info
async function loadTenderWithDetails(tenderId) {
    try {
        const tender = await getTender(tenderId);
        const proposalIds = await getTenderProposals(tenderId);
        
        const proposals = await Promise.all(
            proposalIds.map(async (proposalId) => {
                const proposal = await getProposal(proposalId);
                const vendor = await getVendor(proposal.vendorId);
                const voteCount = await getVoteCount(tenderId, proposal.vendorId);
                
                return {
                    ...proposal,
                    vendor: vendor,
                    votes: voteCount.toNumber()
                };
            })
        );
        
        return {
            ...tender,
            proposals: proposals
        };
    } catch (error) {
        throw new Error(handleContractError(error, 'loading tender details'));
    }
}

// Load project with updates
async function loadProjectWithUpdates(projectId) {
    try {
        const project = await getProject(projectId);
        const updateIds = await getProjectUpdates(projectId);
        
        const updates = await Promise.all(
            updateIds.map(updateId => getProjectUpdate(updateId))
        );
        
        return {
            ...project,
            updates: updates
        };
    } catch (error) {
        throw new Error(handleContractError(error, 'loading project details'));
    }
}

// Export functions to window object for global access
window.submitTaxReturn = submitTaxReturn;
window.validateTaxSubmission = validateTaxSubmission;
window.getTotalRevenue = getTotalRevenue;
window.getTaxSubmission = getTaxSubmission;
window.getCitizenSubmissions = getCitizenSubmissions;
window.getPendingValidations = getPendingValidations;

window.createTender = createTender;
window.registerVendor = registerVendor;
window.submitProposal = submitProposal;
window.citizenVote = citizenVote;
window.finalizeVendor = finalizeVendor;
window.getActiveTenders = getActiveTenders;
window.getTender = getTender;
window.getTenderProposals = getTenderProposals;
window.getProposal = getProposal;
window.getVendor = getVendor;
window.calculateScore = calculateScore;
window.getVoteCount = getVoteCount;

window.getCurrentBalance = getCurrentBalance;
window.deductBudget = deductBudget;
window.getBudgetSummary = getBudgetSummary;
window.getAllDeductions = getAllDeductions;

window.createProject = createProject;
window.postUpdate = postUpdate;
window.flagIssue = flagIssue;
window.getActiveProjects = getActiveProjects;
window.getProject = getProject;
window.getProjectUpdates = getProjectUpdates;
window.getProjectUpdate = getProjectUpdate;

window.formatTenderStatus = formatTenderStatus;
window.formatEvaluationType = formatEvaluationType;
window.formatProjectStatus = formatProjectStatus;
window.formatUpdateType = formatUpdateType;
window.generateIPFSHash = generateIPFSHash;
window.loadTenderWithDetails = loadTenderWithDetails;
window.loadProjectWithUpdates = loadProjectWithUpdates;
