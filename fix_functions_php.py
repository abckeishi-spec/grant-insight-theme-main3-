#!/usr/bin/env python3
"""
functions.phpの関数を条件付き定義に変換するスクリプト
"""

import re

def fix_functions_php():
    """functions.phpを修正"""
    
    # ファイルを読み込み
    with open('functions.php', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 修正が必要な関数のリスト
    functions_to_fix = [
        'gi_ajax_load_grants',
        'gi_ajax_get_search_suggestions',
        'gi_ajax_advanced_search',
        'gi_ajax_grant_insight_search',
        'gi_ajax_toggle_favorite',
        'gi_customize_register',
        'gi_enqueue_scripts',
        'gi_admin_enqueue_scripts',
        'gi_content_width',
        'gi_format_amount_man',
        'gi_get_asset_url',
        'gi_get_upload_url',
        'gi_get_media_url',
        'gi_get_logo_url',
        'gi_safe_get_meta',
        'gi_safe_attr',
        'gi_safe_escape',
        'gi_safe_excerpt',
        'gi_get_formatted_deadline'
    ]
    
    for func_name in functions_to_fix:
        # 関数定義を探す
        pattern = r'(function\s+' + func_name + r'\s*\([^)]*\)\s*\{)'
        
        # すでに条件付きかチェック
        check_pattern = r'if\s*\(\s*!function_exists\s*\(\s*[\'"]' + func_name + r'[\'"]\s*\)\s*\)\s*\{\s*\n\s*function\s+' + func_name
        
        if re.search(check_pattern, content):
            print(f"Already fixed: {func_name}")
            continue
        
        # 関数定義を条件付きに変換
        def replace_func(match):
            return f"if (!function_exists('{func_name}')) {{\n    {match.group(1)}"
        
        new_content = re.sub(pattern, replace_func, content)
        
        if new_content != content:
            # 関数の終わりに閉じ括弧を追加
            # 関数の終わりを見つける（簡易的な方法）
            func_start = new_content.find(f"function {func_name}(")
            if func_start != -1:
                brace_count = 0
                in_function = False
                pos = func_start
                
                for i, char in enumerate(new_content[func_start:], start=func_start):
                    if char == '{':
                        brace_count += 1
                        in_function = True
                    elif char == '}':
                        brace_count -= 1
                        if in_function and brace_count == 0:
                            # 関数の終わりを見つけた
                            # 次の行に閉じ括弧を追加
                            next_newline = new_content.find('\n', i)
                            if next_newline != -1:
                                new_content = new_content[:next_newline] + '\n}' + new_content[next_newline:]
                                break
            
            content = new_content
            print(f"Fixed: {func_name}")
        else:
            print(f"Not found or already fixed: {func_name}")
    
    # add_action呼び出しも条件付きに
    action_pattern = r"(add_action\s*\(\s*['\"]wp_ajax(?:_nopriv)?_[^'\"]+['\"],\s*['\"]([^'\"]+)['\"]\s*\);)"
    
    def fix_action(match):
        func_name = match.group(2)
        if func_name in functions_to_fix:
            return f"if (function_exists('{func_name}')) {{\n    {match.group(1)}\n}}"
        return match.group(1)
    
    content = re.sub(action_pattern, fix_action, content)
    
    # ファイルを保存
    with open('functions-fixed.php', 'w', encoding='utf-8') as f:
        f.write(content)
    
    print("\nSaved to functions-fixed.php")
    print("Please review the changes before replacing functions.php")

if __name__ == "__main__":
    fix_functions_php()