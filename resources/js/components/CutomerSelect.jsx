import React, { useState, useEffect } from "react";
import CreatableSelect from "react-select/creatable";
import axios from "axios";
import { toast } from "sonner";
import getErrorMessage from "../utils/getErrorMessage";
import translate from "../utils/translate";

const CustomerSelect = ({ setCustomerId }) => {
    const [customers, setCustomers] = useState([]);
    const [selectedCustomer, setSelectedCustomer] = useState({
        value: 1,
        label: translate("Walking Customer"),
    });

    // Fetch existing customers from the backend
    useEffect(() => {
      axios.get("/admin/get/customers").then((response) => {
            const customerOptions = response?.data?.map((customer) => ({
                value: customer.id,
                label: customer.name,
            }));
            setCustomers(customerOptions);
        });
    }, []);
  useEffect(() => {
    setCustomerId(selectedCustomer?.value);
  }, [selectedCustomer]);

    const handleCreateCustomer = (inputValue) => {
        axios
            .post("/admin/create/customers", { name: inputValue })
            .then((response) => {
                const newCustomer = response.data;
                const newOption = {
                    value: newCustomer.id,
                    label: newCustomer.name,
                };
                setCustomers((prev) => [newOption,...prev]);
                setSelectedCustomer(newOption);
            })
            .catch((error) => {
                toast.error(getErrorMessage(error));
            });
    };

    const handleChange = (newValue) => {
        setSelectedCustomer(newValue);
    };

    return (
        <CreatableSelect
            isClearable
            options={customers}
            onChange={handleChange}
            onCreateOption={handleCreateCustomer} // Handle creating a new customer
            formatCreateLabel={(inputValue) =>
                `${translate("Create customer:")} ${inputValue}`
            }
            noOptionsMessage={() => translate("No options")}
            value={selectedCustomer}
            placeholder={translate("Select or create customer")}
        />
    );
};

export default CustomerSelect;
