@echo off
echo ====================================
echo     TAXPARENCY SYSTEM TEST
echo ====================================
echo.

echo [1/6] Testing Backend API...
curl -s http://localhost:8000/api/v1/public/statistics > nul
if %errorlevel% == 0 (
    echo ✅ Backend API is running at http://localhost:8000
) else (
    echo ❌ Backend API not accessible. Please start with: cd backend && php artisan serve
)

echo.
echo [2/6] Testing Citizen Login...
curl -s -X POST "http://localhost:8000/api/v1/citizen/login" -H "Content-Type: application/json" -d "{\"tiin\":\"123456789\",\"password\":\"password123\"}" > nul
if %errorlevel% == 0 (
    echo ✅ Citizen login endpoint is working
) else (
    echo ❌ Citizen login endpoint failed
)

echo.
echo [3/6] Checking Database...
if exist "backend\database\database.sqlite" (
    echo ✅ Database file exists
) else (
    echo ❌ Database not found. Run: cd backend && php artisan migrate --seed
)

echo.
echo [4/6] Checking Blockchain Directory...
if exist "blockchain\contracts\TaxReturnRegistry.sol" (
    echo ✅ Smart contracts found
) else (
    echo ❌ Smart contracts not found
)

echo.
echo [5/6] Checking Frontend Files...
if exist "frontend\index.html" (
    echo ✅ Frontend files found
) else (
    echo ❌ Frontend files not found
)

echo.
echo [6/6] System Summary...
echo.
echo 🏛️ TAXPARENCY SYSTEM STATUS:
echo.
echo Backend (Laravel):    http://localhost:8000
echo Frontend:             Open frontend/index.html in browser
echo Blockchain (Ganache): http://localhost:8545 (run: cd blockchain && npm run ganache)
echo.
echo TEST CREDENTIALS:
echo 👤 Citizen:     TIIN: 123456789      Password: password123
echo 🏛️ NBR:         Username: nbr.officer1    Password: nbr123
echo 🏢 Vendor:      Username: abc.construction Password: vendor123
echo 📋 BPPA:        Username: bppa.officer1   Password: bppa123
echo.
echo QUICK START:
echo 1. Start backend:    cd backend && php artisan serve
echo 2. Start blockchain: cd blockchain && npm run ganache
echo 3. Deploy contracts: cd blockchain && npm run deploy
echo 4. Open frontend:    Open frontend/index.html in browser
echo.
echo For full setup guide, see COMPLETE_SETUP.md
echo.
pause
