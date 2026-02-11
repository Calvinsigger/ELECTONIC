# Deploying ELECTONIC to Elastic Beanstalk (quick reference)

Prerequisites:
- Install and configure AWS CLI and EB CLI.
- Have an AWS IAM user with Elastic Beanstalk, EC2, S3, RDS permissions.

Quick one-shot commands (PowerShell):

```powershell
cd C:\xampp\htdocs\Electronic
eb init ELECTONIC --platform "php" --region us-east-1
eb create ELECTONIC-env --instance_type t3.micro --database.engine mysql \
  --database.username ebdbuser --database.password 'ReplaceWithStrongPassword!' --database.size 20 --envvars APP_ENV=production
eb status
eb deploy
eb open
```

After environment is ready, copy EB RDS vars to DB_* env vars (so `api/db.php` works):

```powershell
# Run `eb printenv` and note RDS_HOSTNAME, RDS_PORT, RDS_DB_NAME, RDS_USERNAME, RDS_PASSWORD
eb setenv DB_HOST=<RDS_HOSTNAME> DB_PORT=<RDS_PORT> DB_NAME=<RDS_DB_NAME> DB_USER=<RDS_USERNAME> DB_PASS='<RDS_PASSWORD>'
eb deploy
```

Import database schema:

```powershell
# Using MySQL client (replace placeholders)
mysql -h <DB_HOST> -P <DB_PORT> -u <DB_USER> -p'<DB_PASS>' < database.sql
```

Notes:
- The `.platform/hooks/postdeploy/01_fix_uploads.sh` script ensures `uploads/` is writable after deployment.
- For production, prefer creating a standalone RDS instance and set `DB_*` env vars manually so the DB persists after EB environment termination.
- Do not expose credentials in code or in chat.
