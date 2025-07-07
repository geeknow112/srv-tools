package main

import (
    "database/sql"
    "fmt"
    
    _ "github.com/go-sql-driver/mysql"
)

func main() {
    // MySQLデータベースへの接続情報
    db, err := sql.Open("mysql", "user:password@tcp(localhost:3306)/dbname")
    if err != nil {
        panic(err.Error())
    }
    defer db.Close()

    // データベースへの接続を確認
    err = db.Ping()
    if err != nil {
        panic(err.Error())
    }

    // データの更新クエリ
    updateQuery := "UPDATE your_table SET column1 = ?, column2 = ? WHERE condition_column = ?"

    // クエリを実行
    _, err = db.Exec(updateQuery, "srv-tools#112", "test_value2", "condition_value")
    if err != nil {
        panic(err.Error())
    }

    fmt.Println("データの更新が完了しました")
}
