#!/bin/bash

WORKING_DIR=$(
    cd $(dirname $0)
    pwd
)

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

# install Composer dependencies
cd ${ASSEMBLY_TARGET_DIR}
composer install

cd ${WORKING_DIR}

rm ${ASSEMBLY_DIR}/plugin.tar

# remove files not necessary for public
rm ${ASSEMBLY_TARGET_DIR}/build.sh ${ASSEMBLY_TARGET_DIR}/.gitignore

echo "Creating <integration-dynamics-v${TARGET_VERSION}.zip>..."
cd ${ASSEMBLY_DIR}
zip -r -q -9 integration-dynamics-v${TARGET_VERSION}.zip integration-dynamics/

rm -rf ${ASSEMBLY_DIR}/v${TARGET_VERSION}

mv -f ${ASSEMBLY_TARGET_DIR} ${ASSEMBLY_DIR}/v${TARGET_VERSION}

cd ${WORKING_DIR}

echo "Done. Bye!"
