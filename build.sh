#!/bin/bash

WORKING_DIR=$(
    cd $(dirname $0)
    pwd
)

TOOLKIT_DIR=${WORKING_DIR}/libraries/php-crm-toolkit

ASSEMBLY_DIR=${WORKING_DIR}/../wordpress-crm-release

if [ $1 ]; then
    TARGET_VERSION=$1
else
    echo "Error: No target version specified"
    exit 1
fi

# go to the plugin directory
cd ${WORKING_DIR}

# check that the tag exists
TARGET_TAG_EXISTS=$(git tag | grep ${TARGET_VERSION})
if [ -z ${TARGET_TAG_EXISTS} ]; then
    echo "Error: Specified version tag doesn't exist in the Git repo"
    exit 1
fi

# create assembly directory if none exists
ASSEMBLY_TARGET_DIR=${ASSEMBLY_DIR}/integration-dynamics
mkdir -p ${ASSEMBLY_TARGET_DIR}

# archive the repo
echo "Retrieving plugin v${TARGET_VERSION} files..."
git archive --format=tar -o ${ASSEMBLY_DIR}/plugin.tar ${TARGET_VERSION}

# extract plugin archive
tar -xf ${ASSEMBLY_DIR}/plugin.tar -C ${ASSEMBLY_TARGET_DIR}

# Toolkit revision linked in the given tag
TOOLKIT_REV=$(git ls-tree ${TARGET_VERSION} libraries/php-crm-toolkit/ | awk '{print $3}')

cd ${TOOLKIT_DIR}

# archive toolkit repo
echo "Retrieving toolkit @${TOOLKIT_REV} files..."
git archive --format=tar -o ${ASSEMBLY_DIR}/toolkit.tar ${TOOLKIT_REV}

cd ${WORKING_DIR}

# extract toolkit archive
tar -xf ${ASSEMBLY_DIR}/toolkit.tar -C ${ASSEMBLY_TARGET_DIR}/libraries/php-crm-toolkit

rm ${ASSEMBLY_DIR}/plugin.tar ${ASSEMBLY_DIR}/toolkit.tar

# remove files not necessary for public
rm ${ASSEMBLY_TARGET_DIR}/build.sh ${ASSEMBLY_TARGET_DIR}/.gitignore ${ASSEMBLY_TARGET_DIR}/.gitmodules
rm ${ASSEMBLY_TARGET_DIR}/libraries/php-crm-toolkit/.gitignore
rm -r ${ASSEMBLY_TARGET_DIR}/libraries/php-crm-toolkit/examples ${ASSEMBLY_TARGET_DIR}/libraries/php-crm-toolkit/tests

echo "Creating <integration-dynamics-v${TARGET_VERSION}.zip>..."
cd ${ASSEMBLY_DIR}
zip -r -q -9 integration-dynamics-v${TARGET_VERSION}.zip integration-dynamics/

rm -rf ${ASSEMBLY_DIR}/v${TARGET_VERSION}

mv -f ${ASSEMBLY_TARGET_DIR} ${ASSEMBLY_DIR}/v${TARGET_VERSION}

cd ${WORKING_DIR}

echo "Done. Bye!"
