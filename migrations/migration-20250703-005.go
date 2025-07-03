package main

import (
    "database/sql"
    "fmt"
    
    _ "github.com/go-sql-driver/mysql"
)

func main() {
    // MySQL�f�[�^�x�[�X�ւ̐ڑ����
    db, err := sql.Open("mysql", "user:password@tcp(localhost:3306)/dbname")
    if err != nil {
        panic(err.Error())
    }
    defer db.Close()

    // �f�[�^�x�[�X�ւ̐ڑ����m�F
    err = db.Ping()
    if err != nil {
        panic(err.Error())
    }

    // �f�[�^�̍X�V�N�G��
    updateQuery := "UPDATE your_table SET column1 = ?, column2 = ? WHERE condition_column = ?"

    // �N�G�������s
    _, err = db.Exec(updateQuery, "todo#1986", "new_value2", "condition_value")
    if err != nil {
        panic(err.Error())
    }

    fmt.Println("�f�[�^�̍X�V���������܂���")
}