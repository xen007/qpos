import React, { useState, useEffect } from "react";
import axios from "axios";
import Select from "react-select";
import translate from "../../utils/translate";
import { selectStyles } from "../WorkspaceUI";

export default function Suppliers({ setSupplierId, oldSupplier }) {
    const [suppliers, setSuppliers] = useState([]);
    const [selectedSupplier, setSelectedSupplier] = useState(null);
    useEffect(() => {
        axios.get("/admin/suppliers").then(response => {
            const options=response.data.map(supplier => ({value:supplier.id,label:supplier.is_internal ? translate("Own Supplier") : supplier.name,isInternal:supplier.is_internal}));
            setSuppliers(options);
            if(!oldSupplier) setSelectedSupplier(options.find(supplier => supplier.isInternal) || null);
        });
    }, []);
    useEffect(() => { setSupplierId(selectedSupplier?.value); }, [selectedSupplier]);
    useEffect(() => { setSelectedSupplier(oldSupplier); }, [oldSupplier]);
    return <Select inputId="purchase-supplier" styles={selectStyles} classNamePrefix="qpos-select" isClearable required options={suppliers} value={selectedSupplier} onChange={setSelectedSupplier} placeholder={translate("Select supplier")} noOptionsMessage={() => translate("No options")} />;
}
