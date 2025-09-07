#!/usr/bin/env python3
"""
Phase 1のすべてのPHPファイルで関数定義を条件付きに変換するスクリプト
"""

import os
import re
import shutil

# 修正が必要なファイルリスト
files_to_fix = [
    'category-display.php',
    'customizer-settings.php', 
    'data-validation.php',
    'error-guidance-system.php',
    'grant-counts.php',
    'icon-management.php',
    'individual-categories.php',
    'responsive-accessibility.php',
    'search-filter-stability.php'
]

phase1_dir = '/home/user/webapp/inc/phase1/'

def fix_function_declarations(content):
    """関数定義を条件付きに変換"""
    lines = content.split('\n')
    new_lines = []
    i = 0
    
    while i < len(lines):
        line = lines[i]
        
        # 関数定義を検出 (function functionName( の形式)
        match = re.match(r'^(function\s+)([a-zA-Z_][a-zA-Z0-9_]*)\s*\(', line)
        
        if match:
            func_name = match.group(2)
            # すでに条件付きの場合はスキップ
            if i > 0 and 'function_exists' in lines[i-1]:
                new_lines.append(line)
            else:
                # 条件付き定義に変換
                new_lines.append(f"if (!function_exists('{func_name}')) {{")
                new_lines.append('    ' + line)
                
                # 関数の終わりを見つける
                brace_count = 1
                i += 1
                while i < len(lines) and brace_count > 0:
                    current_line = lines[i]
                    new_lines.append(current_line)
                    brace_count += current_line.count('{') - current_line.count('}')
                    i += 1
                
                # 閉じ括弧を追加
                new_lines.append('}')
                i -= 1
        else:
            new_lines.append(line)
        
        i += 1
    
    return '\n'.join(new_lines)

def process_file(filename):
    """ファイルを処理"""
    filepath = os.path.join(phase1_dir, filename)
    
    if not os.path.exists(filepath):
        print(f"File not found: {filename}")
        return False
    
    # バックアップを作成
    backup_path = filepath.replace('.php', '-original.php')
    if not os.path.exists(backup_path):
        shutil.copy(filepath, backup_path)
        print(f"Created backup: {backup_path}")
    
    # ファイルを読み込み
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 修正を適用
    fixed_content = fix_function_declarations(content)
    
    # ファイルを保存
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(fixed_content)
    
    print(f"Fixed: {filename}")
    return True

def main():
    """メイン処理"""
    print("Starting to fix all Phase 1 PHP files...")
    print("=" * 50)
    
    success_count = 0
    for filename in files_to_fix:
        if process_file(filename):
            success_count += 1
    
    print("=" * 50)
    print(f"Completed: {success_count}/{len(files_to_fix)} files fixed")

if __name__ == "__main__":
    main()