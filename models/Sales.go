package main

import (
	"database/sql"
	"fmt"
	"time"
)

type Sales struct {
	Name string
}

func (s *Sales) GetValidElement(stepNum int) map[string]interface{} {
	messages := map[string]string{
		"name.required":    "ユーザー名を入力してください",
		"name.string":      "正しい形式で入力してください",
		"name.max":         "文字数をオーバーしています。",
		"email.required":   "メールアドレスを入力してください。",
		"email.email":      "正しい形式でメールアドレスを入力してください",
		"email.max":        "文字数をオーバーしています。",
		"email.unique":     "登録済みのユーザーです",
		"password.required": "パスワードを入力してください",
		"password.min":     "パスワードは8文字以上で入力してください。",
		"password.confirmed": "パスワードが一致しません。",
	}

	step1 := map[string]interface{}{
		"rules": map[string]string{
			"customer":            "required|min:1",
			"class":               "required",
			"goods":               "required|max:3",
			"qty":                 "required",
			"delivery_dt":         "required",
			"outgoing_warehouse":  "required",
		},
		"messages": messages,
	}

	step2 := map[string]interface{}{
		"rules": map[string]string{
			"tank": "required|max:100",
			"lot":  "required|max:100",
		},
		"messages": messages,
	}

	switch stepNum {
	default:
		return step1
	case 2:
		return step2
	}
}

func (s *Sales) GetList(get map[string]interface{}, unConvert bool) []map[string]interface{} {
	// Placeholder for database interaction
	var rows []map[string]interface{}
	// Simulate database query and processing
	return rows
}

func (s *Sales) GetDetail(get map[string]interface{}) map[string]interface{} {
	// Placeholder for database interaction
	var row map[string]interface{}
	// Simulate database query and processing
	return row
}

func (s *Sales) GetDetailBySalesCode(sales string) map[string]interface{} {
	// Placeholder for database interaction
	var row map[string]interface{}
	// Simulate database query and processing
	return row
}

func (s *Sales) GetDetailByApplicantCode(applicant string) map[string]interface{} {
	// Placeholder for database interaction
	var row map[string]interface{}
	// Simulate database query and processing
	return row
}

func (s *Sales) GetLotNumberListBySales(get map[string]interface{}) map[string]interface{} {
	// Placeholder for database interaction
	var conv map[string]interface{}
	// Simulate database query and processing
	return conv
}

func (s *Sales) RegDetail(get map[string]interface{}, post map[string]interface{}) map[string]interface{} {
	// Placeholder for database interaction
	var rows map[string]interface{}
	// Simulate database query and processing
	return rows
}

func (s *Sales) UpdDetail(get map[string]interface{}, post map[string]interface{}) map[string]interface{} {
	// Placeholder for database interaction
	var rows map[string]interface{}
	// Simulate database query and processing
	return rows
}

func (s *Sales) UpdLotDetail(get map[string]interface{}, post map[string]interface{}) bool {
	// Placeholder for database interaction
	// Simulate database query and processing
	return true
}

func (s *Sales) MakeLotSpace(get map[string]interface{}, post map[string]interface{}) bool {
	// Placeholder for database interaction
	// Simulate database query and processing
	return true
}

func (s *Sales) MakeLotSpaceSingle(get map[string]interface{}, post map[string]interface{}) map[string]interface{} {
	// Placeholder for database interaction
	var updRet map[string]interface{}
	// Simulate database query and processing
	return updRet
}

func main() {
	// Example usage
	sales := Sales{Name: "yc_sales"}
	fmt.Println(sales.GetValidElement(1))
}
