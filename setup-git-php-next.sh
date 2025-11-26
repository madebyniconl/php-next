#!/usr/bin/env bash
set -e

echo "=== php-next Git setup ==="

# 1) Zorg dat we in de map van het script staan
cd "$(dirname "$0")"

echo "[1/5] Controleren of dit al een git-repo is..."
if [ ! -d .git ]; then
  echo "-> Nog geen .git map gevonden, initialiseer nieuwe repo."
  git init
else
  echo "-> .git bestaat al, ga verder met bestaande repo."
fi

echo
echo "[2/5] Remote URL instellen (GitHub)..."
read -p "Voer de GitHub HTTPS URL in (bv. https://github.com/jouwnaam/php-next.git): " REMOTE_URL

if git remote | grep -q "^origin$"; then
  echo "-> origin bestaat al, update URL..."
  git remote set-url origin "$REMOTE_URL"
else
  echo "-> origin bestaat nog niet, voeg toe..."
  git remote add origin "$REMOTE_URL"
fi

echo
echo "[3/5] Bestanden toevoegen aan commit..."
git add .

echo
echo "[4/5] Commit maken (als er iets te committen is)..."
# Als er niks nieuws is, faalt commit; dat negeren we netjes
git commit -m "Initial php-next commit with PRO ULTRA docs, demo and verification" || echo "-> Geen nieuwe wijzigingen om te committen."

echo
echo "[5/5] Branch instellen op 'main' en pushen naar GitHub..."
git branch -M main
git push -u origin main

echo
echo "=== Klaar! Repo staat nu op GitHub. ==="
