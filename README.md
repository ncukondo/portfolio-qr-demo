# Electronic Portfolio System with QR Code Demo

電子ポートフォリオシステムのデモ実装です。QRコード機能を備えたクラス管理システムを提供します。

## 概要

このプロジェクトは、学習者がクラスを受講し、完了証明をQRコードで取得できるポートフォリオシステムです。PHP 8.2+とPostgreSQLを使用して構築されています。

## 主な機能

- **ユーザー認証**: ログイン・ログアウト機能
- **クラス管理**: クラスの登録、一覧表示
- **QRコード生成**: クラス完了証明のQRコード生成
- **CSV一括インポート**: クラスデータの一括インポート
- **ロールベースアクセス制御**: 管理者、クラス管理者、一般ユーザーの権限管理

## 技術スタック

- **言語**: PHP 8.2+
- **データベース**: PostgreSQL
- **ライブラリ**:
  - Firebase JWT (認証トークン)
  - Endroid QR Code (QRコード生成)
  - PHPUnit (テスト)

## セットアップ

### 推奨：Dev Container を使用した開発環境

このプロジェクトは [Dev Container](https://containers.dev/) を採用しており、一貫した開発環境を簡単に構築できます。

#### 必要なツール

- [Docker](https://www.docker.com/get-started) 
- [Visual Studio Code](https://code.visualstudio.com/)
- [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

#### Dev Container での開始方法

1. **リポジトリをクローン**
   ```bash
   git clone https://github.com/ncukondo/portfolio-qr-demo
   cd portfolio-qr-demo
   ```

2. **VS Code で開く**
   ```bash
   code .
   ```

3. **Dev Container で再開**
   - VS Code で「Reopen in Container」通知をクリック
   - または：Ctrl+Shift+P → 「Dev Containers: Reopen in Container」を選択

4. **自動セットアップ完了を待つ**
   - PHP 8.2 + PostgreSQL 15 環境が自動構築されます
   - Composer依存関係が自動インストールされます
   - Claude Code CLIが自動インストールされます

5. **データベース初期化**
   ```bash
   php public/migrate.php
   php public/seed.php
   ```

6. **アプリケーションにアクセス**
   - ブラウザで `http://localhost:8080` にアクセス

#### Dev Container の構成

- **PHP**: 8.2-apache
- **PostgreSQL**: 15
- **VS Code拡張機能**:
  - PHP Intelephense (コード補完)
  - PHP Debug (デバッグ)
  - PostgreSQL (データベース管理)
- **ポート**:
  - 8080: Webアプリケーション
  - 5432: PostgreSQL

### 手動セットアップ（Dev Container を使わない場合）

#### 1. 依存関係のインストール

```bash
composer install
```

#### 2. データベース設定

環境変数または `config/database.php` で以下の設定を行ってください：

```bash
DB_HOST=localhost
DB_NAME=portfolio_db
DB_USER=portfolio_user
DB_PASSWORD=portfolio_password
DB_PORT=5432
```

#### 3. データベースの初期化

```bash
# マイグレーション実行
php public/migrate.php

# シードデータ投入
php public/seed.php
```

#### 4. 開発サーバーの起動

```bash
php -S 0.0.0.0:8000 -t public/
```

ブラウザで `http://localhost:8000` にアクセスしてください。

## プロジェクト構造

```
├── src/                    # アプリケーションコード
│   ├── Auth/              # 認証機能
│   ├── Controllers/       # コントローラー
│   ├── Database/          # データベース抽象化層
│   ├── Models/           # データモデル
│   └── Services/         # ビジネスロジック
├── public/               # Web公開ディレクトリ
├── config/               # 設定ファイル
├── database/             # マイグレーション・シード
│   ├── migrations/       # データベースマイグレーション
│   └── seeds/           # 初期データ
├── tests/               # テストコード
└── vendor/              # Composer依存関係
```

## 開発ガイドライン

### テスト駆動開発 (TDD)

このプロジェクトはTDDを採用しています：

1. **テストファーストアプローチ**: 新機能実装前に必ずテストを作成
2. **Red-Green-Refactor**: テスト失敗 → 実装 → リファクタリングのサイクル
3. **全テスト実行**: 実装後は必ず全テストスイートを実行

### テストコマンド

```bash
# 全テスト実行
./vendor/bin/phpunit

# 特定テストファイル実行
./vendor/bin/phpunit tests/Unit/Database/DatabaseTest.php

# カバレッジレポート生成
./vendor/bin/phpunit --coverage-html coverage/
```

## 主要ページ

- `/` - システム情報とダッシュボード
- `/classes.php` - クラス一覧
- `/register-class.php` - クラス登録（管理者のみ）
- `/bulk-import-classes.php` - CSV一括インポート（管理者のみ）
- `/generate-completion-url.php` - 完了URL生成（管理者のみ）
- `/login.php` - ログイン

## API エンドポイント

### クラス完了

```
POST /complete-classes.php
```

パラメータ:
- `token`: JWT完了トークン

## データベーススキーマ

### テーブル一覧

- `users` - ユーザー情報
- `classes` - クラス情報
- `credits` - 単位情報
- `class_credits` - クラス-単位関連
- `user_class_completions` - ユーザークラス完了記録

## セキュリティ

- JWT トークンベース認証
- パスワードハッシュ化
- SQLインジェクション対策（PDO prepared statements）
- XSS対策（HTMLエスケープ）

## コントリビューション

1. フィーチャーブランチを作成
2. テストを先に作成（TDD）
3. 実装を行う
4. 全テストが通ることを確認
5. プルリクエストを作成

## ライセンス

このプロジェクトはデモ用途で作成されています。

## トラブルシューティング

### Dev Container関連

#### Dev Container が起動しない
```bash
# Dockerが起動していることを確認
docker ps

# Docker Desktop を再起動
# Windows/Mac: Docker Desktop アプリを再起動
# Linux: sudo systemctl restart docker
```

#### ポート競合エラー
```bash
# ポート8080または5432が使用中の場合
# .devcontainer/compose.yaml のポート設定を変更
ports:
  - "8081:80"  # 8080から8081に変更
```

#### VS Code拡張機能が動作しない
1. Dev Container内でVS Codeが完全に読み込まれるのを待つ
2. Ctrl+Shift+P → 「Developer: Reload Window」で再読み込み

### データベース接続エラー

#### Dev Container使用時
- Dev Container環境では自動的にPostgreSQLが起動します
- DB_HOST=postgres（compose.yamlで設定済み）

#### 手動セットアップ時
1. PostgreSQLサービスが起動していることを確認
2. データベース設定を確認（DB_HOST=localhost）
3. `php public/test-db.php` でデータベース接続をテスト

### テスト失敗

1. テスト用データベースが設定されていることを確認
2. `config/database_test.php` の設定を確認
3. テスト前にデータベースがクリーンな状態であることを確認

### 依存関係エラー

```bash
# Composerキャッシュをクリア
composer clear-cache

# 依存関係を再インストール
rm -rf vendor/
composer install
```

### Dev Container再構築

環境に問題がある場合は、コンテナを再構築してください：

```bash
# VS Code で Ctrl+Shift+P
# 「Dev Containers: Rebuild Container」を選択
```

または Docker CLIで：

```bash
# プロジェクトディレクトリで実行
docker-compose -f .devcontainer/compose.yaml down
docker-compose -f .devcontainer/compose.yaml up --build
```