<Root xmlns="http://www.nexacroplatform.com/platform/dataset">
    <Parameters>
        <Parameter id="WMONID">j19hpCaK9x8</Parameter>
        <Parameter id="_ga">GA1.3.6372961.1636525857</Parameter>
        <Parameter id="_gcl_au">1.1.1331203253.1636620256</Parameter>
        <Parameter id="NetFunnel_ID" />
        <Parameter id="_gid">GA1.3.1166272137.1637800984</Parameter>
        <Parameter id="TS01a96e23">01c5671430b33cfd8789d3f77674310919809231db98cf37dccd9db59458cb16af616009917e43ccc35391e464252da02b3f6e1ff5</Parameter>
        <Parameter id="fsp_action">JJLogin</Parameter>
        <Parameter id="fsp_cmd">login</Parameter>
        <Parameter id="GV_USER_ID">undefined</Parameter>
        <Parameter id="GV_IP_ADDRESS">undefined</Parameter>
        <Parameter id="fsp_logId">all</Parameter>
        <Parameter id="MAX_WRONG_COUNT">5</Parameter>
    </Parameters>
    <Dataset id="ds_cond">
        <ColumnInfo>
            <Column id="SYSTEM_CODE" type="STRING" size="256" />
            <Column id="MEM_ID" type="STRING" size="10" />
            <Column id="MEM_PW" type="STRING" size="15" />
            <Column id="MEM_IP" type="STRING" size="20" />
            <Column id="ROLE_GUBUN1" type="STRING" size="256" />
            <Column id="ROLE_GUBUN2" type="STRING" size="256" />
        </ColumnInfo>
        <Rows>
            <Row>
                <Col id="SYSTEM_CODE">INSTAR_WEB</Col>
                <Col id="MEM_ID">{{ $reqID }}</Col>
                <Col id="MEM_PW">{{ $reqPW }}</Col>
            </Row>
        </Rows>
    </Dataset>
    <Dataset id="fsp_ds_cmd">
        <ColumnInfo>
            <Column id="TX_NAME" type="STRING" size="100" />
            <Column id="TYPE" type="STRING" size="10" />
            <Column id="SQL_ID" type="STRING" size="200" />
            <Column id="KEY_SQL_ID" type="STRING" size="200" />
            <Column id="KEY_INCREMENT" type="INT" size="10" />
            <Column id="CALLBACK_SQL_ID" type="STRING" size="200" />
            <Column id="INSERT_SQL_ID" type="STRING" size="200" />
            <Column id="UPDATE_SQL_ID" type="STRING" size="200" />
            <Column id="DELETE_SQL_ID" type="STRING" size="200" />
            <Column id="SAVE_FLAG_COLUMN" type="STRING" size="200" />
            <Column id="USE_INPUT" type="STRING" size="1" />
            <Column id="USE_ORDER" type="STRING" size="1" />
            <Column id="KEY_ZERO_LEN" type="INT" size="10" />
            <Column id="BIZ_NAME" type="STRING" size="100" />
            <Column id="PAGE_NO" type="INT" size="10" />
            <Column id="PAGE_SIZE" type="INT" size="10" />
            <Column id="READ_ALL" type="STRING" size="1" />
            <Column id="EXEC_TYPE" type="STRING" size="2" />
            <Column id="EXEC" type="STRING" size="1" />
            <Column id="FAIL" type="STRING" size="1" />
            <Column id="FAIL_MSG" type="STRING" size="200" />
            <Column id="EXEC_CNT" type="INT" size="1" />
            <Column id="MSG" type="STRING" size="200" />
        </ColumnInfo>
        <Rows>
        </Rows>
    </Dataset>
    <Dataset id="gds_user">
        <ColumnInfo />
        <Rows>
        </Rows>
    </Dataset>
</Root>