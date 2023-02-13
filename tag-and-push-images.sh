#!/bin/bash -e

# Usage: ./tag-and-push-images.sh ${docker-compose-service-name}
# examples:
#     ./tag-and-push-images.sh alpine
#     ./tag-and-push-images.sh bullseye
#
function main() {
    target="${1}"
    DEFAULT_TAG="bullseye"
    MAJOR_VERSION="1"
    IMAGE="lightswitch05/php-version-audit"

    # Build and tag primary tag name
    echo "Building ${target}"
    docker-compose build --pull "${target}"
    echo "Pushing ${IMAGE}:${target}"
    docker push "${IMAGE}:${target}"

    # Version-based tag name with OS
    echo "Tagging ${IMAGE}:${target} as ${IMAGE}:${MAJOR_VERSION}-${target}"
    docker tag "${IMAGE}:${target}" "${IMAGE}:${MAJOR_VERSION}-${target}"
    echo "Pushing ${IMAGE}:${MAJOR_VERSION}-${target}"
    docker push "${IMAGE}:${MAJOR_VERSION}-${target}"

    # Latest tag name & version-only tag
    if [[ "${target}" = "${DEFAULT_TAG}" ]]; then
        echo "Tagging ${IMAGE}:${target}" "${IMAGE}:latest"
        docker tag "${IMAGE}:${target}" "${IMAGE}:latest"
        echo "Pushing ${IMAGE}:latest"
        docker push "${IMAGE}:latest"

        # Version-based tag name with OS
        echo "Tagging ${IMAGE}:${target} as ${IMAGE}:${MAJOR_VERSION}"
        docker tag "${IMAGE}:${target}" "${IMAGE}:${MAJOR_VERSION}"
        echo "Pushing ${IMAGE}:${MAJOR_VERSION}"
        docker push "${IMAGE}:${MAJOR_VERSION}"
    fi
}

main "$@"
