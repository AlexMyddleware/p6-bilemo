# Check if folder exists and remove subfolder
if (Test-Path "C:\websites\p7-bilemo\var\cache") {
    Set-Location "C:\websites\p7-bilemo\var\cache"
    if (Test-Path "dev") { Remove-item -r dev } else { Write-Host "Folder dev is already deleted" }
    if (Test-Path "test") { Remove-item -r test } else { Write-Host "Folder test is already deleted" }
} else { Write-Host "Folder C:\websites\p7-bilemo\var\cache does not exist" }

# Check if folder exists and remove log files
if (Test-Path "C:\websites\p7-bilemo\var\log") {
    Set-Location "C:\websites\p7-bilemo\var\log"
    if (Test-Path "dev.log") { Remove-item -r dev.log } else { Write-Host "File dev.log is already deleted" }
    if (Test-Path "test.log") { Remove-item -r test.log } else { Write-Host "File test.log is already deleted" }
} else { Write-Host "Folder C:\websites\p7-bilemo\var\log does not exist" }

# Change to project directory
Set-Location "C:\websites\p7-bilemo"

# Run cache clear command on local machine
php bin/console cache:clear

# Run cache clear command in docker container
docker exec p7-bilemo-php-1 php bin/console cache:clear

# Run cache clear command in docker container for test environment
docker exec p7-bilemo-php-1 php bin/console --env=test cache:clear


# Validation message and prompt to exit
Write-Host "Cache cleared successfully in both local and docker environments. Press any key to exit."
$x = $host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")