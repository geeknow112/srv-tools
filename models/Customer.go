package main

import (
	"database/sql"
	"fmt"
	"time"
)

// Assuming ExtModelBase is a struct that provides some base functionality
type ExtModelBase struct {
	// Base fields and methods
}

type Customer struct {
	ExtModelBase
	name string
}

func NewCustomer() *Customer {
	return &Customer{
		name: "yc_customer",
	}
}

// GetValidElement returns validation rules and messages for a given step
func (c *Customer) GetValidElement(stepNum *int) map[string]interface{} {
	step1 := map[string]interface{}{
		"rules": map[string]string{
			"customer_name": "required|max:100",
			// "pref": "required|max:100",
		},
		"messages": map[string]string{
			"name.required": "ユーザー名を入力してください",
			"name.string":   "正しい形式で入力してください",
			"name.max":      "文字数をオーバーしています。",
			"email.required": "メールアドレスを入力してください。",
			"email.email":    "正しい形式でメールアドレスを入力してください",
			"email.max":      "文字数をオーバーしています。",
			"email.unique":   "登録済みのユーザーです",
			"password.required": "パスワードを入力してください",
			"password.min":      "パスワードは8文字以上で入力してください。",
			"password.confirmed": "パスワードが一致しません。",
		},
	}

	return step1
}

// GetList retrieves a list of customers based on the provided parameters
func (c *Customer) GetList(get map[string]interface{}, unConvert bool, db *sql.DB) (interface{}, error) {
	curUser := getCurrentUser()

	sqlQuery := "SELECT c.*, c.name as customer_name FROM yc_customer as c WHERE c.customer IS NOT NULL "

	if !isAdmin(curUser) {
		// sqlQuery += fmt.Sprintf("AND ap.mail = '%s'", curUser.Email)
	}

	if action, ok := get["action"]; !ok || action == "" {
		sqlQuery += ";"
	} else if action == "search" {
		if no, ok := get["s"].(map[string]interface{})["no"]; ok && no != "" {
			sqlQuery += fmt.Sprintf("AND c.customer = '%s' ", no)
		}
		if customerName, ok := get["s"].(map[string]interface{})["customer_name"]; ok && customerName != "" {
			sqlQuery += fmt.Sprintf("AND c.name LIKE '%s%s' ", customerName, "%")
		}
		sqlQuery += ";"
	}

	rows, err := db.Query(sqlQuery)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result map[string][]interface{}
	tmp := make(map[string]map[int]interface{})

	for rows.Next() {
		var row struct {
			DeliveryDt string
			ID         int
		}
		// Assuming the row scan is done here
		// rows.Scan(&row)

		if _, ok := tmp[row.DeliveryDt]; !ok {
			tmp[row.DeliveryDt] = make(map[int]interface{})
		}
		tmp[row.DeliveryDt][row.ID] = row
	}

	// Repeat copy
	test := map[string]interface{}{
		"id":          1,
		"class":       2,
		"delivery_dt": "2022-12-21",
		"goods":       "",
		"goods_name":  "ミルククイーン",
		"ship_addr":   "A棟",
		"qty":         6,
		"arrival_dt":  "2022-12-20",
		"name":        "梅田畜産",
		"repeat_fg":   1,
		"remark":      "",
		"field1":      "",
		"field2":      "",
		"field3":      "",
		"rgdt":        "2023-01-25 06:19:00",
		"updt":        "",
		"upuser":      "",
		"repeat_id":   1,
		"period":      1,
		"span":        5,
		"day_of_week": "",
		"st_dt":       "2022-12-20 00:00:00",
		"ed_dt":       "2023-12-20 00:00:00",
	}

	days10 := []string{"2022-12-20", "2022-12-21", "2022-12-22", "2022-12-23", "2022-12-24", "2022-12-25"}
	for _, day := range days10 {
		t := test
		t["delivery_dt"] = day
		t["goods_name"] = "rep:ミルククイーン"
		result[day] = append(result[day], t)

		if dayData, ok := tmp[day]; ok {
			for _, list := range dayData {
				result[day] = append(result[day], list)
			}
		}
	}

	return result, nil
}

// GetDetail retrieves customer details based on the provided parameters
func (c *Customer) GetDetail(get map[string]interface{}, db *sql.DB) (interface{}, error) {
	curUser := getCurrentUser()

	sqlQuery := fmt.Sprintf("SELECT s.* FROM yc_sales as s WHERE s.id = '%s' LIMIT 1;", get["sales"])

	if !isAdmin(curUser) {
		// sqlQuery += fmt.Sprintf("AND ap.mail = '%s'", curUser.Email)
	}

	rows, err := db.Query(sqlQuery)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result interface{}
	if rows.Next() {
		// Assuming the row scan is done here
		// rows.Scan(&result)
	}

	return result, nil
}

// GetDetailByCustomerCode retrieves customer details by customer code
func (c *Customer) GetDetailByCustomerCode(customer string, db *sql.DB) (interface{}, error) {
	sqlQuery := fmt.Sprintf("SELECT c.*, cd.*, c.customer AS customer FROM %s as c LEFT JOIN yc_customer_detail AS cd ON c.customer = cd.customer WHERE c.customer = '%s';", c.getTableName(), customer)

	rows, err := db.Query(sqlQuery)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result interface{}
	if rows.Next() {
		// Assuming the row scan is done here
		// rows.Scan(&result)
	}

	return result, nil
}

// GetGoodsByCustomerCode retrieves goods associated with a customer code
func (c *Customer) GetGoodsByCustomerCode(customer string, db *sql.DB) (interface{}, error) {
	sqlQuery := fmt.Sprintf("SELECT c.*, g.* FROM %s as c LEFT JOIN yc_customer_goods AS cg ON c.customer = cg.customer LEFT JOIN yc_goods AS g ON cg.goods = g.goods WHERE c.customer = '%s';", c.getTableName(), customer)

	rows, err := db.Query(sqlQuery)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result interface{}
	if rows.Next() {
		// Assuming the row scan is done here
		// rows.Scan(&result)
	}

	return result, nil
}

// GetLotNumberListByOrder retrieves lot numbers for a given order
func (c *Customer) GetLotNumberListByOrder(prm map[string]interface{}, db *sql.DB) (interface{}, error) {
	curUser := getCurrentUser()

	sqlQuery := fmt.Sprintf("SELECT o.id, o.ship_addr, o.arrival_dt, o.name, g.goods, g.name as goods_name, g.qty as goods_qty, gd.lot, gd.tank FROM yc_sales as o LEFT JOIN yc_goods as g ON o.goods = g.goods LEFT JOIN yc_goods_detail as gd on o.id = gd.order WHERE o.id IS NOT NULL AND gd.id IS NOT NULL AND o.id = %d and g.goods = %d;", prm["order"], prm["goods"])

	rows, err := db.Query(sqlQuery)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result interface{}
	if rows.Next() {
		// Assuming the row scan is done here
		// rows.Scan(&result)
	}

	return result, nil
}

// RegDetail registers customer details
func (c *Customer) RegDetail(get map[string]interface{}, post map[string]interface{}, db *sql.DB) (interface{}, error) {
	existColumns, err := getColumns(db, c.getTableName())
	if err != nil {
		return nil, err
	}

	data := make(map[string]interface{})
	for _, col := range existColumns {
		if val, ok := post[col]; ok && val != "" {
			data[col] = val
		}
	}

	data["name"] = post["customer_name"]
	data["rgdt"] = time.Now().Format("2006-01-02 15:04:05")

	// Assuming the insert operation is done here
	// ret, err := db.Exec(insertQuery, data)

	customerID := 0 // Assuming the last insert ID is retrieved here

	for i, tank := range post["tank"].([]interface{}) {
		detail := i + 1
		// Assuming the insert operation for customer detail is done here
		// retDetail, err := db.Exec(insertDetailQuery, customerID, detail, tank)
	}

	if goodsS, ok := post["goods_s"]; ok {
		for _, goods := range goodsS.([]interface{}) {
			// Assuming the insert operation for customer goods is done here
			// retGoodsS, err := db.Exec(insertGoodsQuery, customerID, goods)
		}
	}

	rows, err := c.GetDetailByCustomerCode(fmt.Sprintf("%d", customerID), db)
	if err != nil {
		return nil, err
	}

	return rows, nil
}

// UpdDetail updates customer details
func (c *Customer) UpdDetail(get map[string]interface{}, post map[string]interface{}, db *sql.DB) (interface{}, error) {
	post["name"] = post["customer_name"]

	existColumns, err := getColumns(db, c.getTableName())
	if err != nil {
		return nil, err
	}

	data := make(map[string]interface{})
	for _, col := range existColumns {
		if val, ok := post[col]; ok && val != "" {
			data[col] = val
		}
	}

	data["updt"] = time.Now().Format("2006-01-02 15:04:05")

	// Assuming the update operation is done here
	// ret, err := db.Exec(updateQuery, data, post["customer"])

	if list, ok := post["list"]; ok {
		for i, d := range list.([]interface{}) {
			detail := i + 1
			// Assuming the upsert operation for customer detail is done here
			// retAddrs, err := db.Exec(upsertDetailQuery, post["customer"], detail, d)
		}
	}

	if goodsS, ok := post["goods_s"]; ok {
		// Assuming the delete operation for customer goods is done here
		// retDel, err := db.Exec(deleteGoodsQuery, post["customer"])

		for _, goods := range goodsS.([]interface{}) {
			// Assuming the insert operation for customer goods is done here
			// retGoodsS, err := db.Exec(insertGoodsQuery, post["customer"], goods)
		}
	}

	rows, err := c.GetDetailByCustomerCode(fmt.Sprintf("%v", post["customer"]), db)
	if err != nil {
		return nil, err
	}

	return rows, nil
}

// Helper functions
func (c *Customer) getTableName() string {
	return c.name
}

func getCurrentUser() *User {
	// Placeholder for getting the current user
	return &User{}
}

func isAdmin(user *User) bool {
	// Placeholder for checking if the user is an admin
	return false
}

func getColumns(db *sql.DB, tableName string) ([]string, error) {
	// Placeholder for getting columns from a table
	return []string{}, nil
}

type User struct {
	// User fields
}

func main() {
	// Example usage
}
