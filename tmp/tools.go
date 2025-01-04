package main

import (
	"fmt"
)

type SalesController struct {
	// Assuming Ext_Controller_Action has some fields or methods
}

type PostData struct {
	RepeatFg       bool
	BaseSales      interface{}
	BaseDeliveryDt interface{}
	DeliveryDt     interface{}
	Sales          interface{}
}

type GetData struct {
	// Define fields as needed
}

type RepeatExclude struct {
	// Define fields as needed
}

func (re *RepeatExclude) UpdDetail(get *GetData, post *PostData) {
	// Implement the method
}

type ScheduleRepeat struct {
	// Define fields as needed
}

func (sr *ScheduleRepeat) CopyDetail(get *GetData, post *PostData) {
	// Implement the method
}

func (sc *SalesController) ConvertSalesData(post *PostData) {
	// Implement the method
}

func (sc *SalesController) GetTb() *Table {
	// Return an instance of Table
	return &Table{}
}

type Table struct {
	// Define fields as needed
}

func (t *Table) CopyDetail(get *GetData, post *PostData) *PostData {
	// Implement the method and return PostData
	return post
}

func (t *Table) InitRepeatFg(post *PostData) bool {
	// Implement the method and return a boolean
	return true
}

func (sc *SalesController) RegistOrderProcessForRepeat(get *GetData, post *PostData) interface{} {
	// salesテーブルへ登録のための成形
	sc.ConvertSalesData(post)

	// salesテーブルへ登録
	post.RepeatFg = true
	rows := sc.GetTb().CopyDetail(get, post)

	// repeat_excludeテーブルに必要な情報を追加
	post.Sales = post.BaseSales
	if post.BaseDeliveryDt != nil {
		post.DeliveryDt = post.BaseDeliveryDt
	}

	// repeat_excludeテーブルへ登録
	repeatExclude := &RepeatExclude{}
	repeatExclude.UpdDetail(get, post)

	// 元注文の繰り返しOFF
	initBool := sc.GetTb().InitRepeatFg(post)

	// 元注文の繰り返しを新注文へコピーする
	post.Sales = rows.Sales
	post.DeliveryDt = rows.DeliveryDt // repeat_s_dtに新しいdelivery_dtを設定
	scheduleRepeat := &ScheduleRepeat{}
	scheduleRepeat.CopyDetail(get, post)

	return rows.Sales
}

func main() {
	// Example usage
	fmt.Println("SalesController example")
}

