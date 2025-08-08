// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

contract ProjectTracker {
    enum ProjectStatus { Active, Completed, Delayed, Flagged }
    enum UpdateType { Milestone, Receipt, Justification, Issue }

    struct Project {
        uint256 id;
        uint256 tenderId;
        uint256 vendorId;
        uint256 budget;
        uint256 startDate;
        uint256 expectedEndDate;
        ProjectStatus status;
        string title;
        string descriptionHash; // IPFS hash
    }

    struct ProjectUpdate {
        uint256 id;
        uint256 projectId;
        uint256 vendorId;
        UpdateType updateType;
        string dataHash; // IPFS hash for documents/receipts
        string description;
        uint256 timestamp;
        bool verified;
        address verifiedBy;
    }

    struct Issue {
        uint256 id;
        uint256 projectId;
        string reason;
        string evidenceHash; // IPFS hash
        address reportedBy;
        uint256 timestamp;
        bool resolved;
        string resolutionHash; // IPFS hash for resolution documents
    }

    mapping(uint256 => Project) public projects;
    mapping(uint256 => ProjectUpdate) public projectUpdates;
    mapping(uint256 => Issue) public issues;
    mapping(uint256 => uint256[]) public projectUpdatesList; // projectId => updateIds[]
    mapping(uint256 => uint256[]) public projectIssuesList; // projectId => issueIds[]
    mapping(address => bool) public authorizedTrackers; // BPPA officials who can create projects
    mapping(address => bool) public authorizedVerifiers; // Officials who can verify updates

    uint256 public projectCounter;
    uint256 public updateCounter;
    uint256 public issueCounter;
    
    address public owner;

    event ProjectCreated(
        uint256 indexed projectId,
        uint256 indexed tenderId,
        uint256 indexed vendorId,
        uint256 budget,
        string title
    );

    event UpdatePosted(
        uint256 indexed updateId,
        uint256 indexed projectId,
        uint256 indexed vendorId,
        UpdateType updateType,
        string description,
        string dataHash
    );

    event IssueReported(
        uint256 indexed issueId,
        uint256 indexed projectId,
        address indexed reportedBy,
        string reason
    );

    event UpdateVerified(
        uint256 indexed updateId,
        address indexed verifiedBy,
        uint256 timestamp
    );

    event ProjectStatusChanged(
        uint256 indexed projectId,
        ProjectStatus oldStatus,
        ProjectStatus newStatus,
        address changedBy
    );

    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can perform this action");
        _;
    }

    modifier onlyAuthorizedTracker() {
        require(authorizedTrackers[msg.sender], "Only authorized trackers can perform this action");
        _;
    }

    modifier onlyAuthorizedVerifier() {
        require(authorizedVerifiers[msg.sender], "Only authorized verifiers can perform this action");
        _;
    }

    modifier onlyProjectVendor(uint256 _projectId) {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        // This would need to be integrated with TenderContract to get vendor address
        _;
    }

    constructor() {
        owner = msg.sender;
        authorizedTrackers[msg.sender] = true;
        authorizedVerifiers[msg.sender] = true;
    }

    function addAuthorizedTracker(address _tracker) external onlyOwner {
        authorizedTrackers[_tracker] = true;
    }

    function addAuthorizedVerifier(address _verifier) external onlyOwner {
        authorizedVerifiers[_verifier] = true;
    }

    function removeAuthorizedTracker(address _tracker) external onlyOwner {
        authorizedTrackers[_tracker] = false;
    }

    function removeAuthorizedVerifier(address _verifier) external onlyOwner {
        authorizedVerifiers[_verifier] = false;
    }

    function createProject(
        uint256 _tenderId,
        uint256 _vendorId,
        uint256 _budget,
        uint256 _expectedEndDate,
        string memory _title,
        string memory _descriptionHash
    ) external onlyAuthorizedTracker {
        require(_budget > 0, "Budget must be greater than 0");
        require(_expectedEndDate > block.timestamp, "End date must be in future");
        require(bytes(_title).length > 0, "Title required");

        projectCounter++;

        projects[projectCounter] = Project({
            id: projectCounter,
            tenderId: _tenderId,
            vendorId: _vendorId,
            budget: _budget,
            startDate: block.timestamp,
            expectedEndDate: _expectedEndDate,
            status: ProjectStatus.Active,
            title: _title,
            descriptionHash: _descriptionHash
        });

        emit ProjectCreated(projectCounter, _tenderId, _vendorId, _budget, _title);
    }

    function postUpdate(
        uint256 _projectId,
        UpdateType _updateType,
        string memory _dataHash,
        string memory _description
    ) external {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        require(bytes(_description).length > 0, "Description required");
        
        Project storage project = projects[_projectId];
        // In a real implementation, you'd verify the sender is the project vendor
        
        updateCounter++;

        projectUpdates[updateCounter] = ProjectUpdate({
            id: updateCounter,
            projectId: _projectId,
            vendorId: project.vendorId,
            updateType: _updateType,
            dataHash: _dataHash,
            description: _description,
            timestamp: block.timestamp,
            verified: false,
            verifiedBy: address(0)
        });

        projectUpdatesList[_projectId].push(updateCounter);

        emit UpdatePosted(updateCounter, _projectId, project.vendorId, _updateType, _description, _dataHash);
    }

    function verifyUpdate(uint256 _updateId) external onlyAuthorizedVerifier {
        require(_updateId > 0 && _updateId <= updateCounter, "Invalid update ID");
        require(!projectUpdates[_updateId].verified, "Update already verified");

        projectUpdates[_updateId].verified = true;
        projectUpdates[_updateId].verifiedBy = msg.sender;

        emit UpdateVerified(_updateId, msg.sender, block.timestamp);
    }

    function flagIssue(
        uint256 _projectId,
        string memory _reason,
        string memory _evidenceHash
    ) external {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        require(bytes(_reason).length > 0, "Reason required");

        issueCounter++;

        issues[issueCounter] = Issue({
            id: issueCounter,
            projectId: _projectId,
            reason: _reason,
            evidenceHash: _evidenceHash,
            reportedBy: msg.sender,
            timestamp: block.timestamp,
            resolved: false,
            resolutionHash: ""
        });

        projectIssuesList[_projectId].push(issueCounter);

        // Auto-flag the project
        Project storage project = projects[_projectId];
        if (project.status != ProjectStatus.Flagged) {
            ProjectStatus oldStatus = project.status;
            project.status = ProjectStatus.Flagged;
            emit ProjectStatusChanged(_projectId, oldStatus, ProjectStatus.Flagged, msg.sender);
        }

        emit IssueReported(issueCounter, _projectId, msg.sender, _reason);
    }

    function resolveIssue(
        uint256 _issueId,
        string memory _resolutionHash
    ) external onlyAuthorizedVerifier {
        require(_issueId > 0 && _issueId <= issueCounter, "Invalid issue ID");
        require(!issues[_issueId].resolved, "Issue already resolved");
        require(bytes(_resolutionHash).length > 0, "Resolution hash required");

        issues[_issueId].resolved = true;
        issues[_issueId].resolutionHash = _resolutionHash;

        // Check if all issues for the project are resolved
        uint256 projectId = issues[_issueId].projectId;
        bool allResolved = true;
        uint256[] memory projectIssues = projectIssuesList[projectId];
        
        for (uint256 i = 0; i < projectIssues.length; i++) {
            if (!issues[projectIssues[i]].resolved) {
                allResolved = false;
                break;
            }
        }

        // If all issues resolved, change status back to Active
        if (allResolved) {
            Project storage project = projects[projectId];
            if (project.status == ProjectStatus.Flagged) {
                ProjectStatus oldStatus = project.status;
                project.status = ProjectStatus.Active;
                emit ProjectStatusChanged(projectId, oldStatus, ProjectStatus.Active, msg.sender);
            }
        }
    }

    function changeProjectStatus(
        uint256 _projectId,
        ProjectStatus _newStatus
    ) external onlyAuthorizedTracker {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        
        Project storage project = projects[_projectId];
        ProjectStatus oldStatus = project.status;
        project.status = _newStatus;

        emit ProjectStatusChanged(_projectId, oldStatus, _newStatus, msg.sender);
    }

    function getStatus(uint256 _projectId) external view returns (ProjectStatus) {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        return projects[_projectId].status;
    }

    function getProject(uint256 _projectId) external view returns (Project memory) {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        return projects[_projectId];
    }

    function getProjectUpdate(uint256 _updateId) external view returns (ProjectUpdate memory) {
        require(_updateId > 0 && _updateId <= updateCounter, "Invalid update ID");
        return projectUpdates[_updateId];
    }

    function getProjectUpdates(uint256 _projectId) external view returns (uint256[] memory) {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        return projectUpdatesList[_projectId];
    }

    function getProjectIssues(uint256 _projectId) external view returns (uint256[] memory) {
        require(_projectId > 0 && _projectId <= projectCounter, "Invalid project ID");
        return projectIssuesList[_projectId];
    }

    function getIssue(uint256 _issueId) external view returns (Issue memory) {
        require(_issueId > 0 && _issueId <= issueCounter, "Invalid issue ID");
        return issues[_issueId];
    }

    function getActiveProjects() external view returns (uint256[] memory) {
        uint256[] memory activeProjectIds = new uint256[](projectCounter);
        uint256 activeCount = 0;

        for (uint256 i = 1; i <= projectCounter; i++) {
            if (projects[i].status == ProjectStatus.Active) {
                activeProjectIds[activeCount] = i;
                activeCount++;
            }
        }

        uint256[] memory result = new uint256[](activeCount);
        for (uint256 i = 0; i < activeCount; i++) {
            result[i] = activeProjectIds[i];
        }

        return result;
    }

    function getDelayedProjects() external view returns (uint256[] memory) {
        uint256[] memory delayedProjectIds = new uint256[](projectCounter);
        uint256 delayedCount = 0;

        for (uint256 i = 1; i <= projectCounter; i++) {
            if (projects[i].status == ProjectStatus.Delayed || 
                (projects[i].status == ProjectStatus.Active && block.timestamp > projects[i].expectedEndDate)) {
                delayedProjectIds[delayedCount] = i;
                delayedCount++;
            }
        }

        uint256[] memory result = new uint256[](delayedCount);
        for (uint256 i = 0; i < delayedCount; i++) {
            result[i] = delayedProjectIds[i];
        }

        return result;
    }

    function getFlaggedProjects() external view returns (uint256[] memory) {
        uint256[] memory flaggedProjectIds = new uint256[](projectCounter);
        uint256 flaggedCount = 0;

        for (uint256 i = 1; i <= projectCounter; i++) {
            if (projects[i].status == ProjectStatus.Flagged) {
                flaggedProjectIds[flaggedCount] = i;
                flaggedCount++;
            }
        }

        uint256[] memory result = new uint256[](flaggedCount);
        for (uint256 i = 0; i < flaggedCount; i++) {
            result[i] = flaggedProjectIds[i];
        }

        return result;
    }
}
