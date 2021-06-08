#!/bin/bash -e

# Usage: ./tag-and-push-images.sh ${docker-compose-service-name}
# examples:
#     ./tag-and-push-images.sh alpine
#     ./tag-and-push-images.sh buster
#
function main() {
    target="${1}"
    DEFAULT_TAG="buster"
    MAJOR_VERSION="1"
    IMAGE="lightswitch05/php-version-audit"

    # Build and tag primary tag name
    docker-compose build --pull "${target}"
    docker push "${IMAGE}:${target}"

    # Version-based tag name
    docker tag "${IMAGE}:${target}" "${IMAGE}:${MAJOR_VERSION}-${target}"
    docker push "${IMAGE}:${target}"

    # Latest tag name
    if [[ "${target}" = "${DEFAULT_TAG}" ]]; then
        docker tag "${IMAGE}:${target}" "${IMAGE}:latest"
        docker push "${IMAGE}:latest"
    fi
}

main "$@"
