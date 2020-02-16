#!/usr/bin/env bash

sModuleName="Bill"
sModuleKey="bill"
sPath="/home/praesidiarius/PhpstormProjects/SDG_X_Stable/module"

# Copy Skeleton
cp -R "${sPath}/Skeleton" "${sPath}/${sModuleName}"

# Remove GIT and Scripts
rm -rf "${sPath}/${sModuleName}/.git"
rm -f "${sPath}/${sModuleName}/data/*.sh"
rm -f "${sPath}/${sModuleName}/data/*.ps1"

# Rename Files and Folders
mv "${sPath}/${sModuleName}/src/Controller/SkeletonController.php" "${sPath}/${sModuleName}/src/Controller/${sModuleName}Controller.php"
mv "${sPath}/${sModuleName}/src/Form/SkeletonForm.php" "${sPath}/${sModuleName}/src/Form/${sModuleName}Form.php"
mv "${sPath}/${sModuleName}/src/Model/Skeleton.php" "${sPath}/${sModuleName}/src/Model/${sModuleName}.php"
mv "${sPath}/${sModuleName}/src/Model/SkeletonTable.php" "${sPath}/${sModuleName}/src/Model/${sModuleName}Table.php"
mv "${sPath}/${sModuleName}/view/skeleton/skeleton" "${sPath}/${sModuleName}/view/skeleton/${sModuleKey}"
mv "${sPath}/${sModuleName}/view/skeleton" "${sPath}/${sModuleName}/view/${sModuleKey}"

# Search Replace in Code
sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/src/Controller/${sModuleName}Controller.php"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/src/Controller/${sModuleName}Controller.php"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/src/Form/${sModuleName}Form.php"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/src/Form/${sModuleName}Form.php"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/src/Model/${sModuleName}.php"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/src/Model/${sModuleName}.php"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/src/Model/${sModuleName}Table.php"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/src/Model/${sModuleName}Table.php"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/add.phtml"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/add.phtml"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/edit.phtml"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/edit.phtml"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/view.phtml"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/view.phtml"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/index.phtml"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/index.phtml"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/delete.phtml"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/view/${sModuleKey}/${sModuleKey}/delete.phtml"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/src/Module.php"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/src/Module.php"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/config/module.config.php"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/config/module.config.php"

sed -i "s/Skeleton/${sModuleName}/g" "${sPath}/${sModuleName}/data/install.sql"
sed -i "s/skeleton/${sModuleKey}/g" "${sPath}/${sModuleName}/data/install.sql"