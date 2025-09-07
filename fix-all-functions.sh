#!/bin/bash

# Phase 1ディレクトリ
PHASE1_DIR="/home/user/webapp/inc/phase1"

# 修正が必要なファイルリスト
FILES=(
    "ai-diagnosis.php"
    "category-display.php"
    "customizer-settings.php"
    "data-validation.php"
    "error-guidance-system.php"
    "grant-counts.php"
    "icon-management.php"
    "individual-categories.php"
    "responsive-accessibility.php"
    "search-filter-stability.php"
)

echo "Starting to fix function duplicates in all Phase 1 files..."

for FILE in "${FILES[@]}"; do
    FILEPATH="$PHASE1_DIR/$FILE"
    
    if [ ! -f "$FILEPATH" ]; then
        echo "File not found: $FILE"
        continue
    fi
    
    # バックアップ作成
    BACKUP="${FILEPATH}.backup"
    if [ ! -f "$BACKUP" ]; then
        cp "$FILEPATH" "$BACKUP"
        echo "Created backup: $BACKUP"
    fi
    
    # 一時ファイル作成
    TEMP_FILE="${FILEPATH}.tmp"
    
    # 関数定義を条件付きに変換
    # このsedコマンドは function name( を検出して条件付きに変換
    sed -E '
        # 関数定義行を検出して置換
        /^function[[:space:]]+[a-zA-Z_][a-zA-Z0-9_]*[[:space:]]*\(/ {
            # 関数名を抽出
            s/^function[[:space:]]+([a-zA-Z_][a-zA-Z0-9_]*)[[:space:]]*\(.*$/if (!function_exists('\''&1'\'')) {\n&/
        }
    ' "$FILEPATH" > "$TEMP_FILE"
    
    # 結果をファイルに戻す
    mv "$TEMP_FILE" "$FILEPATH"
    
    echo "Fixed: $FILE"
done

echo "All files have been processed!"