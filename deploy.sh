#!/usr/bin/env bash

UPSTREAM=${1:-'@{u}'}
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse "$UPSTREAM")
BASE=$(git merge-base @ "$UPSTREAM")
BRANCH=$(git rev-parse --abbrev-ref HEAD)
REPO=$(basename `git rev-parse --show-toplevel`)
PINK='\033[1;35m'
RED='\033[0;31m'
NC='\033[0m'

if [ "$BRANCH" != "development" ]; then
  echo -e "${RED}CANNOT DEPLOY: You can only deploy from the 'development' branch${NC}"
elif [[ `git status --porcelain` ]]; then
  echo -e "${RED}CANNOT DEPLOY: There are local changes${NC}"
elif [ $LOCAL = $REMOTE ]; then
  echo -e "${PINK}DEPLOYING${NC}"
  echo -e "${PINK}STEP 0 of 5: Hold on to your butts${NC}"
  echo -e "${PINK}STEP 1 of 5: Checking out 'production' branch${NC}"
  git checkout production
  echo -e "${PINK}STEP 2 of 5: Pulling latest from 'production' branch${NC}"
  git pull
  echo -e "${PINK}STEP 3 of 5: Bringing 'production' branch up-to-date with 'development'${NC}"
  git pull origin development
  echo -e "${PINK}STEP 4 of 5: Pushing changes to 'production' branch${NC}"
  git push
  echo -e "${PINK}STEP 5 of 5: Navigating back to 'development'${NC}"
  git checkout development
  echo -e "${PINK}FINISHED LOCAL DEPLOYMENT TASKS${NC}"
elif [ $LOCAL = $BASE ]; then
  echo -e "${RED}CANNOT DEPLOY: Need to pull${NC}"
elif [ $REMOTE = $BASE ]; then
  echo -e "${RED}CANNOT DEPLOY: Need to push${NC}"
else
  echo -e "${RED}CANNOT DEPLOY: Diverged${NC}"
fi