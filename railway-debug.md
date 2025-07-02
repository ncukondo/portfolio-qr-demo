# Railway デバッグ手順

## 1. Railway CLI でのサービス確認
```bash
# サービス一覧確認
railway service list

# PostgreSQL サービスの詳細確認
railway service

# 環境変数確認
railway variables

# ログ確認
railway logs --service postgresql
railway logs --service web
```

## 2. よくある問題と解決策

### PostgreSQL サービスが起動していない
```bash
# サービスを再起動
railway service restart --service postgresql
```

### DATABASE_URL が正しく設定されていない
- Railway Dashboard → PostgreSQL Service → Connect タブ
- 表示された DATABASE_URL をコピー
- Variables タブで手動設定

### 接続が拒否される場合
1. PostgreSQL サービスが完全に起動するまで2-3分待つ
2. Web サービスを再デプロイ
3. 両方のサービスが同じプロジェクト内にあることを確認

## 3. 確認すべき設定
- ✅ PostgreSQL サービスが "Running" 状態
- ✅ DATABASE_URL 環境変数が設定済み  
- ✅ Web サービスから PostgreSQL サービスへのネットワーク接続許可
- ✅ PostgreSQL ポート 5432 が開放されている