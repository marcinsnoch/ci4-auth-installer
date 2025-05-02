#!/bin/bash

CI4_PATH=$1

if [ -z "$CI4_PATH" ]; then
  echo "Użycie: ./install.sh /ścieżka/do/projektu_ci4"
  exit 1
fi

echo "Instaluję MyAuth do projektu w: $CI4_PATH"

# 1. Kopiowanie plików
echo "📂 Kopiuję pliki konfiguracyjne i przykładowe..."
cp -r app/Config/* "$CI4_PATH/app/Config/"
cp -r app/Controllers/* "$CI4_PATH/app/Controllers/"
cp -r app/Database/* "$CI4_PATH/app/Database/"
cp -r app/Filters/* "$CI4_PATH/app/Filters/"
cp -r app/Language/pl/* "$CI4_PATH/app/Language/pl/"
cp -r app/Libraries/* "$CI4_PATH/app/Libraries/"
cp -r app/Models/* "$CI4_PATH/app/Models/"
mkdir -p "$CI4_PATH/app/Views/emails/"
cp -r app/Views/emails/* "$CI4_PATH/app/Views/emails/"

# 2. Run migration & seeder
cd $CI4_PATH
php spark migrate:rollback -f
php spark migrate && php spark db:seed AllSeeders

# 2. Add to GIT
echo "GIT Commit"
git add . && git commit -m "MyAuth installed"

echo "Dodaj sekcję EMAIL do .env"
