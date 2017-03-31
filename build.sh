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
TARGET_COMMIT_EXISTS=$(git rev-list --all | grep ${TARGET_VERSION})
if [ -z ${TARGET_TAG_EXISTS} ] && [ -z ${TARGET_COMMIT_EXISTS} ]; then
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
composer install --prefer-dist

cd ${WORKING_DIR}

rm ${ASSEMBLY_DIR}/plugin.tar

# remove files not necessary for public
rm ${ASSEMBLY_TARGET_DIR}/build.sh ${ASSEMBLY_TARGET_DIR}/.gitignore

# Remove unnecessary 3rd party files from packages
rm -r ${ASSEMBLY_TARGET_DIR}/vendor/alexacrm/php-crm-toolkit/examples
rm -r ${ASSEMBLY_TARGET_DIR}/vendor/alexacrm/php-crm-toolkit/tests
rm -r ${ASSEMBLY_TARGET_DIR}/vendor/monolog/monolog/doc
rm -r ${ASSEMBLY_TARGET_DIR}/vendor/monolog/monolog/tests
rm -r ${ASSEMBLY_TARGET_DIR}/vendor/symfony/http-foundation/Tests

echo "Creating <integration-dynamics-v${TARGET_VERSION}.zip>..."
cd ${ASSEMBLY_DIR}

# Remove the old ZIP if it exists
rm -f integration-dynamics-v${TARGET_VERSION}.zip

zip -r -q -9 integration-dynamics-v${TARGET_VERSION}.zip integration-dynamics/

rm -rf ${ASSEMBLY_DIR}/v${TARGET_VERSION}

mv -f ${ASSEMBLY_TARGET_DIR} ${ASSEMBLY_DIR}/v${TARGET_VERSION}

cd ${WORKING_DIR}

echo "Done. Bye!"
