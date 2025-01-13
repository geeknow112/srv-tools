package models

import (
	"database/sql"
	"fmt"
	"time"
)

// Goods represents the goods model structure
type Goods struct {
	db   *sql.DB
	name string
}

// ValidationRules represents the validation rules structure
type ValidationRules struct {
	Rules    map[string]string
	Messages map[string]string
}

// NewGoods creates a new instance of Goods
func NewGoods(db *sql.DB) *Goods {
	return &Goods{
		db:   db,
		name: "yc_goods",
	}
}

// GetValidElement returns validation rules for different steps
func (g *Goods) GetValidElement(stepNum interface{}) ValidationRules {
	step1 := ValidationRules{
		Rules: map[string]string{
			"goods_name": "required|max:100",
			"qty":        "required|max:100",
		},
		Messages: map[string]string{
			"name.required":     "ユーザー名を入力してください",
			"name.string":       "正しい形式で入力してください",
			"name.max":          "文字数をオーバーしています。",
			"email.required":    "メールアドレスを入力してください。",
			"email.email":       "正しい形式でメールアドレスを入力してください",
			"email.max":         "文字数をオーバーしています。",
			"email.unique":      "登録済みのユーザーです",
			"password.required": "パスワードを入力してください",
			"password.min":      "パスワードは8文字以上で入力してください。",
			"password.confirmed": "パスワードが一致しません。",
		},
	}

	return step1
}

// GetList retrieves the list of goods
func (g *Goods) GetList(params map[string]interface{}) ([]map[string]interface{}, error) {
	query := `
		SELECT g.*, g.name AS goods_name
		FROM yc_goods AS g
		WHERE g.goods IS NOT NULL
	`

	// Add conditions based on user role and params
	if action, ok := params["action"].(string); ok {
		if action == "search" {
			if no, ok := params["s"].(map[string]interface{})["no"]; ok {
				query += fmt.Sprintf(" AND g.goods = '%v'", no)
			}
			if goodsName, ok := params["s"].(map[string]interface{})["goods_name"]; ok {
				query += fmt.Sprintf(" AND g.name LIKE '%v%%'", goodsName)
			}
		}
	}

	query += ";"

	rows, err := g.db.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result []map[string]interface{}
	// Process rows and return results
	// Implementation details omitted for brevity

	return result, nil
}

// GetDetail retrieves detailed information about a specific good
func (g *Goods) GetDetail(params map[string]interface{}) (map[string]interface{}, error) {
	query := `
		SELECT s.* FROM yc_sales as s
		WHERE s.id = ?
		LIMIT 1
	`

	rows, err := g.db.Query(query, params["sales"])
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result map[string]interface{}
	// Process first row and return result
	// Implementation details omitted for brevity

	return result, nil
}

// GetDetailByGoodsCode retrieves goods details by goods code
func (g *Goods) GetDetailByGoodsCode(goods interface{}) (map[string]interface{}, error) {
	query := fmt.Sprintf(`
		SELECT g.* FROM %s as g
		WHERE g.goods = ?
		LIMIT 1
	`, g.name)

	rows, err := g.db.Query(query, goods)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result map[string]interface{}
	// Process first row and return result
	// Implementation details omitted for brevity

	return result, nil
}

// RegDetail registers new goods details
func (g *Goods) RegDetail(get, post map[string]interface{}) (map[string]interface{}, error) {
	// Implementation for inserting new goods
	// Details omitted for brevity
	return nil, nil
}

// UpdDetail updates existing goods details
func (g *Goods) UpdDetail(get map[string]interface{}, post map[string]interface{}) (map[string]interface{}, error) {
	data := make(map[string]interface{})
	
	// Set update fields
	data["updt"] = time.Now().Format("2006-01-02 15:04:05")
	// Add other fields from post data

	// Perform update
	// Implementation details omitted for brevity

	return g.GetDetailByGoodsCode(post["goods"])
}

// GetInitForm returns initial form data
func (g *Goods) GetInitForm() map[string]interface{} {
	return map[string]interface{}{
		"select": map[string]interface{}{
			"goods_name": g.getPartsGoodsName(),
		},
	}
}

// getPartsGoodsName retrieves goods names for parts
func (g *Goods) getPartsGoodsName() map[string]string {
	result := make(map[string]string)
	
	goods, err := g.GetList(nil)
	if err != nil {
		return result
	}

	for _, d := range goods {
		if name, ok := d["name"]; ok {
			separately := ""
			if separatelyFg, ok := d["separately_fg"].(bool); ok && separatelyFg {
				separately = " （バラ）"
			}
			result[fmt.Sprint(d["goods"])] = fmt.Sprintf("%v%s", name, separately)
		}
	}

	return result
}
