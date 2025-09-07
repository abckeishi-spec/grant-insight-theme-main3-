<?php

namespace GrantInsight\Tests\Unit;

use PHPUnit\Framework\TestCase;
use GrantInsight\Helpers\Formatting;

/**
 * Formatting Helper Unit Tests
 */
class FormattingTest extends TestCase
{
    /**
     * 数値フォーマットのテスト
     */
    public function testSafeNumberFormat()
    {
        // 正常な数値
        $this->assertEquals('1,000', Formatting::safeNumberFormat(1000));
        $this->assertEquals('1,000.50', Formatting::safeNumberFormat(1000.5, 2));
        
        // 無効な値
        $this->assertEquals('0', Formatting::safeNumberFormat('invalid'));
        $this->assertEquals('0', Formatting::safeNumberFormat(null));
        $this->assertEquals('0', Formatting::safeNumberFormat(''));
    }

    /**
     * 日付フォーマットのテスト
     */
    public function testSafeDateFormat()
    {
        // 正常な日付
        $this->assertEquals('2024-01-15', Formatting::safeDateFormat('2024-01-15'));
        $this->assertEquals('2024年1月15日', Formatting::safeDateFormat('2024-01-15', 'Y年n月j日'));
        
        // タイムスタンプ
        $timestamp = strtotime('2024-01-15');
        $this->assertEquals('2024-01-15', Formatting::safeDateFormat($timestamp));
        
        // 無効な日付
        $this->assertEquals('', Formatting::safeDateFormat('invalid-date'));
        $this->assertEquals('', Formatting::safeDateFormat(''));
        $this->assertEquals('', Formatting::safeDateFormat(null));
    }

    /**
     * パーセンテージフォーマットのテスト
     */
    public function testSafePercentFormat()
    {
        $this->assertEquals('75.0%', Formatting::safePercentFormat(75));
        $this->assertEquals('75.5%', Formatting::safePercentFormat(75.5));
        $this->assertEquals('75.50%', Formatting::safePercentFormat(75.5, 2));
        
        // 無効な値
        $this->assertEquals('0.0%', Formatting::safePercentFormat('invalid'));
    }

    /**
     * 抜粋フォーマットのテスト
     */
    public function testSafeExcerpt()
    {
        $long_text = 'これは非常に長いテキストです。この文章は100文字を超える長さになるように作成されています。テストのために十分な長さが必要です。';
        
        // 通常の抜粋
        $excerpt = Formatting::safeExcerpt($long_text, 50);
        $this->assertLessThanOrEqual(53, mb_strlen($excerpt)); // "..." を含む
        $this->assertStringEndsWith('...', $excerpt);
        
        // 短いテキスト
        $short_text = '短いテキスト';
        $this->assertEquals($short_text, Formatting::safeExcerpt($short_text, 100));
    }

    /**
     * 助成額フォーマットのテスト
     */
    public function testFormatAmountMan()
    {
        // 万円単位
        $this->assertEquals('100万円', Formatting::formatAmountMan(1000000));
        $this->assertEquals('1,500万円', Formatting::formatAmountMan(15000000));
        
        // 億円単位
        $this->assertEquals('1.0億円', Formatting::formatAmountMan(100000000));
        $this->assertEquals('2.5億円', Formatting::formatAmountMan(250000000));
        
        // 万円未満
        $this->assertEquals('5,000円', Formatting::formatAmountMan(5000));
        
        // テキスト指定
        $this->assertEquals('上限なし', Formatting::formatAmountMan(0, '上限なし'));
        
        // 無効な値
        $this->assertEquals('金額未設定', Formatting::formatAmountMan(0));
        $this->assertEquals('金額未設定', Formatting::formatAmountMan(-1));
    }

    /**
     * 申請ステータスマッピングのテスト
     */
    public function testMapApplicationStatusUi()
    {
        // 既知のステータス
        $status = Formatting::mapApplicationStatusUi('approved');
        $this->assertEquals('承認', $status['label']);
        $this->assertStringContains('green', $status['class']);
        $this->assertStringContains('check-circle', $status['icon']);
        
        // 未知のステータス（デフォルト）
        $status = Formatting::mapApplicationStatusUi('unknown_status');
        $this->assertEquals('未申請', $status['label']);
    }

    /**
     * 安全なエスケープのテスト
     */
    public function testSafeEscape()
    {
        // HTMLタグの除去
        $this->assertEquals('テストテキスト', Formatting::safeEscape('<script>alert("test")</script>テストテキスト'));
        $this->assertEquals('リンクテキスト', Formatting::safeEscape('<a href="http://example.com">リンクテキスト</a>'));
        
        // 特殊文字のエスケープ
        $this->assertEquals('&lt;test&gt;', Formatting::safeEscape('<test>'));
    }

    /**
     * 安全な属性値のテスト
     */
    public function testSafeAttr()
    {
        // HTMLタグの除去と属性エスケープ
        $this->assertEquals('test-class', Formatting::safeAttr('<span>test-class</span>'));
        $this->assertEquals('value&quot;test', Formatting::safeAttr('value"test'));
    }
}

