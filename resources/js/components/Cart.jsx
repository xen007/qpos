import axios from "axios";
import React, { useState, useEffect } from "react";
import { toast } from "sonner";
import Swal from "sweetalert2";
import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import getErrorMessage from "../utils/getErrorMessage";
import playSound from "../utils/playSound";
import translate from "../utils/translate";
import { Minus, Plus, Trash2 } from "lucide-react";

export default function Cart({ carts, setCartUpdated, cartUpdated }) {
    function increment(id) {
        axios
            .put("/admin/cart/increment", {
                id: id,
            })
            .then((res) => {
                setCartUpdated(!cartUpdated);
                playSound(SuccessSound);
                toast.success(res?.data?.message);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(getErrorMessage(err));
            });
    }
    function decrement(id) {
        axios
            .put("/admin/cart/decrement", {
                id: id,
            })
            .then((res) => {
                setCartUpdated(!cartUpdated);
                playSound(SuccessSound);
                toast.success(res?.data?.message);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(getErrorMessage(err));
            });
    }
    function destroy(id) {
        Swal.fire({
            title: translate("Are you sure you want to delete this item?"),
            showDenyButton: true,
            confirmButtonText: translate("Yes"),
            denyButtonText: translate("No"),
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/cart/delete", {
                        id: id,
                    })
                    .then((res) => {
                        setCartUpdated(!cartUpdated);
                        playSound(SuccessSound);
                        toast.success(res?.data?.message);
                    })
                    .catch((err) => {
                        toast.error(getErrorMessage(err));
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }
    return (
        <>
            <div className="user-cart">
                <div className="card">
                    <div className="card-body">
                        <div className="responsive-table">
                            <table className="table table-striped">
                                <thead>
                                    <tr className="text-center">
                                        <th>{translate("Name")}</th>
                                        <th>{translate("Quantity")}</th>
                                        <th></th>
                                        <th>{translate("Price")}</th>
                                        <th>{translate("Total")}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {carts.map((item) => (
                                        <tr key={item.id}>
                                            <td>{item.product.name}</td>
                                            <td className="d-flex align-items-center">
                                                <button
                                                    className="btn btn-warning btn-sm"
                                                    aria-label={translate(
                                                        "Decrease quantity"
                                                    )}
                                                    onClick={() =>
                                                        decrement(item.id)
                                                    }
                                                >
                                                    <Minus size={14} aria-hidden="true" />
                                                </button>
                                                <input
                                                    type="number"
                                                    className="form-control form-control-sm qty ml-1 mr-1"
                                                    value={item.quantity}
                                                    disabled
                                                />
                                                <button
                                                    className="btn btn-success btn-sm"
                                                    aria-label={translate(
                                                        "Increase quantity"
                                                    )}
                                                    onClick={() =>
                                                        increment(item.id)
                                                    }
                                                >
                                                    <Plus size={14} aria-hidden="true" />
                                                </button>
                                            </td>
                                            <td>
                                                <button
                                                    className="btn btn-danger btn-sm mr-3"
                                                    aria-label={translate(
                                                        "Remove item"
                                                    )}
                                                    onClick={() =>
                                                        destroy(item.id)
                                                    }
                                                >
                                                    <Trash2 size={14} aria-hidden="true" />
                                                </button>
                                            </td>
                                            <td className="text-right">
                                                {item?.product?.discounted_price}
                                                {item?.product?.price >
                                                item?.product
                                                    ?.discounted_price ? (
                                                    <>
                                                        <br />
                                                        <del>
                                                            {item?.product?.price}
                                                        </del>
                                                    </>
                                                ) : (
                                                    ""
                                                )}
                                            </td>
                                            <td className="text-right">
                                                {item?.row_total}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
