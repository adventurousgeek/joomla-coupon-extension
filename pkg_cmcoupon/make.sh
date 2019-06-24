#!/bin/sh
cd `dirname "$(readlink -f "$0")"`
rm packages/*.zip ../pkg_cmcoupon.zip

cd components/com_cmcoupon/
zip -r ../../packages/com_cmcoupon.zip *
cd $OLDPWD

cd plugins/plg_vmshipment_cmcoupon/
zip -r ../../packages/plg_vmshipment_cmcoupon *
cd $OLDPWD

cd plugins/plg_vmpayment_cmcoupon/
zip -r ../../packages/plg_vmpayment_cmcoupon.zip *
cd $OLDPWD

cd plugins/plg_vmcalculation_cmcoupon/
zip -r ../../packages/plg_vmcalculation_cmcoupon.zip *
cd $OLDPWD

cd plugins/plg_vmcoupon_cmcoupon/
zip -r ../../packages/plg_vmcoupon_cmcoupon.zip *
cd $OLDPWD

cd plugins/plg_system_cmcoupon/
zip -r ../../packages/plg_system_cmcoupon.zip *
cd $OLDPWD

zip -r ../pkg_cmcoupon.zip pkg_cmcoupon.xml packages
cd $OLDPWD

